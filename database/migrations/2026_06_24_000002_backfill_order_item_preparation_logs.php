<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Backfill order_item_preparation_logs from existing order_items data.
     *
     * For each existing order_item that has preparing_at set:
     * - Creates one row per unit of quantity in the new tracking table
     * - If the item was delivered, calculates preparation_minutes
     * - Maps the order_item status to the log item_status
     */
    public function up(): void
    {
        // Fetch all order items that have preparation data
        $orderItems = DB::table('order_items')
            ->whereNotNull('preparing_at')
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();

        $batchSize = 500;
        $rows = [];
        $count = 0;

        foreach ($orderItems as $orderItem) {
            $quantity = (int) $orderItem->quantity;
            $deliveredQuantity = (int) ($orderItem->delivered_quantity ?? 0);
            $preparingAt = $orderItem->preparing_at;
            $deliveredAt = $orderItem->last_delivered_at ?? $orderItem->delivered_at;

            // Map order_item status to preparation log status
            $baseStatus = $this->mapStatus($orderItem->status);

            // Determine the item_display_name
            $displayName = $orderItem->item_display_name;
            if (empty($displayName)) {
                $item = DB::table('items')->where('id', $orderItem->item_id)->first();
                $displayName = $item->name ?? 'Unknown';
            }

            for ($i = 0; $i < $quantity; $i++) {
                $isDelivered = $i < $deliveredQuantity;
                $logStatus = $isDelivered ? 'delivered' : $baseStatus;

                // For delivered units, calculate preparation_minutes
                $logDeliveredAt = null;
                $prepMinutes = null;

                if ($isDelivered && $preparingAt && $deliveredAt) {
                    $logDeliveredAt = $deliveredAt;
                    $prepMinutes = (int) ((strtotime($deliveredAt) - strtotime($preparingAt)) / 60);
                    if ($prepMinutes < 0) {
                        $prepMinutes = 0;
                    }
                }

                // For cancelled/deleted items, mark all rows with that status
                if (in_array($orderItem->status, ['cancelled', 'deleted'])) {
                    $logStatus = $orderItem->status;
                    $logDeliveredAt = null;
                    $prepMinutes = null;
                }

                $rows[] = [
                    'order_id' => $orderItem->order_id,
                    'order_item_id' => $orderItem->id,
                    'item_id' => $orderItem->item_id,
                    'item_modifier_id' => $orderItem->item_modifier_id ?? null,
                    'item_display_name' => $displayName,
                    'quantity' => 1,
                    'item_status' => $logStatus,
                    'prepared_at' => $preparingAt,
                    'delivered_at' => $logDeliveredAt,
                    'preparation_minutes' => $prepMinutes,
                    'created_at' => $orderItem->created_at,
                    'updated_at' => $orderItem->updated_at,
                ];

                $count++;

                // Insert in batches to avoid memory issues
                if (count($rows) >= $batchSize) {
                    DB::table('order_item_preparation_logs')->insert($rows);
                    $rows = [];
                }
            }
        }

        // Insert remaining rows
        if (!empty($rows)) {
            DB::table('order_item_preparation_logs')->insert($rows);
        }

        Log::info("Backfilled {$count} preparation log records from existing order_items data.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncate the backfilled data (preserves any new records created after migration)
        // This is a data migration, so down() clears the backfilled data
        DB::table('order_item_preparation_logs')
            ->whereRaw('created_at < ?', [now()])
            ->delete();
    }

    /**
     * Map order_item status to preparation log status.
     */
    private function mapStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'pending',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'served' => 'served',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'deleted' => 'deleted',
            default => 'preparing',
        };
    }
};
