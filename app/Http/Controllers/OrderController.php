<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Item;
use App\Models\RestaurantStock;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $table = Table::findOrFail($validated['table_id']);

            // Create or get existing order
            $order = $table->currentOrder;

            if (!$order) {
                $order = Order::create([
                    'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(Order::all()->filter(fn ($orderRecord) => $orderRecord->created_at?->isToday())->count() + 1, 4, '0', STR_PAD_LEFT),
                    'table_id' => $table->id,
                    'waiter_id' => Auth::id(),
                    'status' => 'pending',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                ]);

                $table->update([
                    'status' => 'ordered',
                    'current_order_id' => $order->id
                ]);
            }

            // Add items to order
            foreach ($validated['items'] as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);

                $subtotal = $item->price * $itemData['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'quantity' => $itemData['quantity'],
                    'latest_added_quantity' => $itemData['quantity'],
                    'delivered_quantity' => 0,
                    'unit_price' => $item->price,
                    'subtotal' => $subtotal,
                    'status' => 'preparing',
                    'preparing_at' => now(),
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                ]);

                // Deduct from restaurant stock for finished goods items
                if ($item->is_finished_goods) {
                    RestaurantStock::deductForSale(
                        $item->id,
                        null, // No modifier support in simple order controller
                        $itemData['quantity'],
                        Auth::id()
                    );
                }
            }

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items added to order successfully',
                'order' => $order->load('items.item')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:order_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);
            $orderItem = OrderItem::query()->where('order_id', $order->id)
                ->where('id', $validated['item_id'])
                ->firstOrFail();

            $trackingUpdate = $validated['quantity'] > $orderItem->quantity
                ? $orderItem->quantityIncreaseTrackingAttributes($validated['quantity'])
                : $orderItem->quantityDecreaseTrackingAttributes($validated['quantity']);

            $orderItem->update(array_merge($trackingUpdate, [
                'quantity' => $validated['quantity'],
                'subtotal' => $orderItem->unit_price * $validated['quantity'],
            ]));

            $this->recalculateOrderTotals($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'order' => $order->load('items.item')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $orderItem = OrderItem::findOrFail($id);
            $order = $orderItem->order;

            // Mark item as deleted instead of hard delete
            $orderItem->update(['status' => 'deleted']);

            // If no active items left, delete the order and update table
            if ($order->items()->active()->count() === 0) {
                $table = $order->table;
                if ($table) {
                    $table->update([
                        'status' => 'available',
                        'current_order_id' => null
                    ]);
                }
                $order->delete();
            } else {
                $this->recalculateOrderTotals($order);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item: ' . $e->getMessage()
            ], 500);
        }
    }

    private function recalculateOrderTotals($order)
    {
        $taxService = app(TaxService::class);
        $activeItems = $order->items()->active()->with('item')->get();
        $taxTotals = $taxService->calculateOrderTax($activeItems);

        $order->update([
            'subtotal' => $taxTotals['subtotal'],
            'sscl_amount' => $taxTotals['sscl_amount'],
            'vat_amount' => $taxTotals['vat_amount'],
            'tax_amount' => $taxTotals['tax_amount'],
            'total_amount' => $taxTotals['total_amount'],
        ]);
    }
}
