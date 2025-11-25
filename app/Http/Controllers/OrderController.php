<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Item;
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
                    'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
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
                    'unit_price' => $item->price,
                    'subtotal' => $subtotal,
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                ]);
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
            $orderItem = OrderItem::where('order_id', $order->id)
                ->where('id', $validated['item_id'])
                ->firstOrFail();

            $orderItem->update([
                'quantity' => $validated['quantity'],
                'subtotal' => $orderItem->unit_price * $validated['quantity'],
            ]);

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
            
            $orderItem->delete();

            // If no items left, delete the order and update table
            if ($order->items()->count() === 0) {
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
        $subtotal = $order->items()->sum('subtotal');
        $taxRate = 0.10; // 10% tax
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
        ]);
    }
}
