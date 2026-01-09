<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\MainStockItem;
use App\Models\CashierSubStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * Display the supervisor's stock transfer page (create transfers).
     */
    public function supervisorIndex()
    {
        // Get all active main stock items (raw materials)
        $mainStockItems = MainStockItem::active()
            ->orderBy('item_name')
            ->get();

        // Get transfer history by this supervisor
        $myTransfers = StockTransfer::with(['items', 'cashier'])
            ->bySupervisor(Auth::id())
            ->latest()
            ->paginate(10);

        // Get counts for tabs
        $counts = [
            'pending' => StockTransfer::bySupervisor(Auth::id())->pending()->count(),
            'accepted' => StockTransfer::bySupervisor(Auth::id())->accepted()->count(),
            'rejected' => StockTransfer::bySupervisor(Auth::id())->rejected()->count(),
        ];

        return view('stock.supervisor.transfers.index', compact('mainStockItems', 'myTransfers', 'counts'));
    }

    /**
     * Store a new stock transfer from supervisor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:main_stock_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Create stock transfer
            $transfer = StockTransfer::create([
                'supervisor_id' => Auth::id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $mainStockItem = MainStockItem::find($item['item_id']);

                // Check if main stock has enough quantity
                if ($mainStockItem->quantity < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$mainStockItem->item_name}. Available: {$mainStockItem->quantity} {$mainStockItem->unit_abbreviation}");
                }

                // Create transfer item
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'main_stock_item_id' => $mainStockItem->id,
                    'item_name' => $mainStockItem->item_name,
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer created successfully!',
                'transfer_number' => $transfer->transfer_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transfer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transfer details.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items.mainStockItem', 'supervisor', 'cashier']);

        return response()->json([
            'success' => true,
            'data' => $stockTransfer,
        ]);
    }

    /**
     * Display the cashier's incoming transfers page.
     */
    public function cashierIndex()
    {
        // Get pending transfers (awaiting cashier acceptance)
        $pendingTransfers = StockTransfer::with(['items', 'supervisor'])
            ->pending()
            ->latest()
            ->get();

        // Get accepted transfers history
        $acceptedTransfers = StockTransfer::with(['items', 'supervisor'])
            ->accepted()
            ->latest()
            ->limit(20)
            ->get();

        // Get rejected transfers history
        $rejectedTransfers = StockTransfer::with(['items', 'supervisor'])
            ->rejected()
            ->latest()
            ->limit(20)
            ->get();

        $counts = [
            'pending' => $pendingTransfers->count(),
            'accepted' => $acceptedTransfers->count(),
            'rejected' => $rejectedTransfers->count(),
        ];

        return view('stock.cashier.transfers', compact('pendingTransfers', 'acceptedTransfers', 'rejectedTransfers', 'counts'));
    }

    /**
     * Cashier responds to a stock transfer.
     */
    public function cashierRespond(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        // Only process pending transfers
        if ($stockTransfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This transfer has already been processed.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $isAccepted = $request->action === 'accept';

            if ($isAccepted) {
                // Check if main stock still has enough for all items
                foreach ($stockTransfer->items as $item) {
                    $mainStockItem = MainStockItem::find($item->main_stock_item_id);
                    if (!$mainStockItem || $mainStockItem->quantity < $item->quantity) {
                        throw new \Exception("Not enough stock available for {$item->item_name}");
                    }
                }

                // Process the transfer
                foreach ($stockTransfer->items as $item) {
                    // Deduct from main stock
                    $mainStockItem = MainStockItem::find($item->main_stock_item_id);
                    $mainStockItem->deductStock($item->quantity, Auth::id(), 'transfer_to_restaurant', $stockTransfer->transfer_number, 'Transfer to cashier sub-stock');

                    // Add to cashier sub stock
                    $subStock = CashierSubStock::getOrCreateForItem($item->main_stock_item_id);
                    $subStock->addStock($item->quantity, Auth::id());

                    // Update item status
                    $item->update(['status' => 'accepted']);
                }

                $stockTransfer->update([
                    'status' => 'accepted',
                    'cashier_id' => Auth::id(),
                    'cashier_notes' => $request->notes,
                    'responded_at' => now(),
                ]);
            } else {
                // Reject the transfer
                $stockTransfer->items()->update(['status' => 'rejected']);

                $stockTransfer->update([
                    'status' => 'rejected',
                    'cashier_id' => Auth::id(),
                    'cashier_notes' => $request->notes,
                    'responded_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isAccepted
                    ? 'Transfer accepted! Stock has been added to your inventory.'
                    : 'Transfer has been rejected.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process transfer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending transfers for cashier (AJAX).
     */
    public function getPendingTransfers()
    {
        $transfers = StockTransfer::with(['items', 'supervisor'])
            ->pending()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transfers,
            'count' => $transfers->count(),
        ]);
    }

    /**
     * Display the cashier's sub stock page.
     */
    public function cashierSubStock(Request $request)
    {
        $type = $request->get('type', 'raw_material'); // Default to raw_material

        $subStock = CashierSubStock::with('mainStockItem')
            ->whereHas('mainStockItem', function ($q) use ($type) {
                $q->active()->where('item_type', $type);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('stock.cashier.sub-stock', compact('subStock', 'type'));
    }

    /**
     * Get cashier's sub stock (AJAX).
     */
    public function getSubStock()
    {
        $subStock = CashierSubStock::with('mainStockItem')
            ->whereHas('mainStockItem', function ($q) {
                $q->active();
            })
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'item_name' => $stock->display_name,
                    'item_code' => $stock->mainStockItem->item_code,
                    'quantity' => $stock->quantity,
                    'unit' => $stock->unit_abbreviation,
                    'item_type' => $stock->mainStockItem->item_type_label,
                    'updated_at' => $stock->updated_at->format('M d, Y h:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subStock,
        ]);
    }

    /**
     * Get supervisor's transfer history.
     */
    public function getTransferHistory(Request $request)
    {
        $query = StockTransfer::with(['items', 'cashier'])
            ->bySupervisor(Auth::id());

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transfers = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }
}
