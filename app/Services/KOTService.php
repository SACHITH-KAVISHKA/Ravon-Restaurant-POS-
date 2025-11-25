<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Kot;
use App\Models\KotItem;
use Illuminate\Support\Facades\DB;
use Exception;

class KOTService
{
    public function __construct(
        protected PrinterService $printerService
    ) {}

    /**
     * Generate KOTs for an order.
     */
    public function generateKOTsForOrder(Order $order): array
    {
        $kots = [];
        
        // Group items by kitchen station
        $itemsByStation = $order->orderItems()
            ->with(['item.kitchenStation', 'modifiers.modifier'])
            ->get()
            ->groupBy('item.kitchen_station_id');

        foreach ($itemsByStation as $stationId => $items) {
            if (!$stationId) continue; // Skip items without station
            
            $kot = $this->createKOT($order, $stationId, $items);
            $kots[] = $kot;
            
            // Auto-print KOT
            $this->printKOT($kot);
        }

        return $kots;
    }

    /**
     * Create a KOT.
     */
    protected function createKOT(Order $order, int $stationId, $items): Kot
    {
        DB::beginTransaction();
        try {
            $kot = Kot::create([
                'order_id' => $order->id,
                'kitchen_station_id' => $stationId,
                'table_id' => $order->table_id,
                'waiter_id' => $order->waiter_id,
                'status' => 'pending',
            ]);

            // Add items to KOT
            foreach ($items as $orderItem) {
                $modifiers = $orderItem->modifiers->map(function ($mod) {
                    return [
                        'name' => $mod->modifier->name,
                        'price' => $mod->price_adjustment,
                    ];
                })->toArray();

                KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $orderItem->id,
                    'item_name' => $orderItem->item->name,
                    'quantity' => $orderItem->quantity,
                    'special_instructions' => $orderItem->special_instructions,
                    'modifiers' => $modifiers,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // Broadcast to kitchen
            broadcast(new \App\Events\KOTGenerated($kot));

            return $kot->fresh(['kotItems', 'kitchenStation', 'order', 'table']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Print KOT.
     */
    public function printKOT(Kot $kot): bool
    {
        try {
            // Format KOT for printing
            $printData = $this->formatKOTForPrint($kot);
            
            // Send to printer
            $result = $this->printerService->printKOT(
                $kot->kitchenStation,
                $printData
            );

            // Mark as printed
            $kot->markAsPrinted();

            return $result;

        } catch (Exception $e) {
            logger()->error('KOT Print Failed', [
                'kot_id' => $kot->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format KOT for printing.
     */
    protected function formatKOTForPrint(Kot $kot): array
    {
        return [
            'kot_number' => $kot->kot_number,
            'order_number' => $kot->order->order_number,
            'table' => $kot->table?->table_number ?? 'Takeaway',
            'floor' => $kot->table?->floor->name ?? '',
            'waiter' => $kot->waiter?->name ?? 'N/A',
            'order_type' => strtoupper(str_replace('_', ' ', $kot->order->order_type)),
            'time' => now()->format('H:i'),
            'date' => now()->format('d/m/Y'),
            'items' => $kot->kotItems->map(function ($item) {
                return [
                    'quantity' => $item->quantity,
                    'name' => $item->item_name,
                    'modifiers' => $item->modifiers,
                    'instructions' => $item->special_instructions,
                ];
            })->toArray(),
            'special_instructions' => $kot->order->special_instructions,
        ];
    }

    /**
     * Reprint KOT.
     */
    public function reprintKOT(int $kotId): bool
    {
        $kot = Kot::with(['kotItems', 'kitchenStation', 'order', 'table'])->findOrFail($kotId);
        return $this->printKOT($kot);
    }

    /**
     * Update KOT status.
     */
    public function updateKOTStatus(Kot $kot, string $status): Kot
    {
        $kot->update(['status' => $status]);

        if ($status === 'ready') {
            $kot->update(['completed_at' => now()]);
            
            // Update order items
            $kot->kotItems()->update(['status' => 'ready']);
            
            // Notify waiter
            broadcast(new \App\Events\KOTReady($kot));
        }

        return $kot->fresh();
    }

    /**
     * Update KOT item status.
     */
    public function updateKOTItemStatus(KotItem $kotItem, string $status): KotItem
    {
        $kotItem->update(['status' => $status]);

        // Update corresponding order item
        $kotItem->orderItem->update(['status' => $status]);

        // Check if all items in KOT are ready
        $allReady = $kotItem->kot->kotItems()->where('status', '!=', 'ready')->count() === 0;
        if ($allReady) {
            $this->updateKOTStatus($kotItem->kot, 'ready');
        }

        return $kotItem->fresh();
    }

    /**
     * Get pending KOTs for a station.
     */
    public function getPendingKOTsForStation(int $stationId)
    {
        return Kot::where('kitchen_station_id', $stationId)
            ->whereIn('status', ['pending', 'preparing'])
            ->with(['order', 'table', 'kotItems'])
            ->latest()
            ->get();
    }

    /**
     * Get KOT statistics.
     */
    public function getKOTStatistics()
    {
        return [
            'today_total' => Kot::today()->count(),
            'pending' => Kot::pending()->count(),
            'preparing' => Kot::preparing()->count(),
            'avg_preparation_time' => $this->calculateAveragePreparationTime(),
        ];
    }

    /**
     * Calculate average preparation time.
     */
    protected function calculateAveragePreparationTime(): ?float
    {
        $kots = Kot::whereNotNull('printed_at')
            ->whereNotNull('completed_at')
            ->whereDate('created_at', today())
            ->get();

        if ($kots->isEmpty()) {
            return null;
        }

        $totalMinutes = $kots->sum(function ($kot) {
            return $kot->printed_at->diffInMinutes($kot->completed_at);
        });

        return round($totalMinutes / $kots->count(), 2);
    }
}
