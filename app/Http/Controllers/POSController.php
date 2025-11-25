<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class POSController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // No service dependencies needed
    }

    /**
     * Display the POS interface.
     */
    public function index()
    {
        $categories = Category::active()
            ->ordered()
            ->with(['availableItems' => function ($query) {
                $query->available()->orderBy('display_order')->with('modifiers');
            }])
            ->get();

        $tables = Table::orderBy('table_number')->get();

        return view('pos.index', compact('categories', 'tables'));
    }

    /**
     * Get item details for POS.
     */
    public function getItem($id)
    {
        $item = Item::with(['modifiers' => function ($query) {
            $query->where('is_active', true);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'item' => $item
        ]);
    }

    /**
     * Process POS order and payment.
     */
    public function processOrder(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:dine-in,take-away,delivery',
            'table_id' => 'nullable|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.modifiers' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,upi,split',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . time() . '-' . rand(1000, 9999),
                'order_type' => $validated['order_type'],
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'waiter_id' => Auth::id(),
                'status' => 'completed', // Direct order completion for POS
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            // Add items to order
            $subtotal = 0;
            foreach ($validated['items'] as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price'],
                    'subtotal' => $itemData['price'] * $itemData['quantity'],
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                ]);

                // Add modifiers if any
                if (!empty($itemData['modifiers'])) {
                    foreach ($itemData['modifiers'] as $modifier) {
                        $orderItem->modifiers()->create([
                            'modifier_name' => $modifier['name'],
                            'modifier_price' => $modifier['price'] ?? 0,
                        ]);
                        $orderItem->subtotal += $modifier['price'] ?? 0;
                    }
                    $orderItem->save();
                }

                $subtotal += $orderItem->subtotal;
            }

            // Apply discount
            $discountAmount = 0;
            if (!empty($validated['discount_type']) && !empty($validated['discount_value'])) {
                if ($validated['discount_type'] === 'percentage') {
                    $discountAmount = ($subtotal * $validated['discount_value']) / 100;
                } else {
                    $discountAmount = $validated['discount_value'];
                }
            }

            // Calculate totals
            $taxRate = 0; // You can configure this
            $taxAmount = 0; // ($subtotal - $discountAmount) * ($taxRate / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'service_charge' => 0,
                'total_amount' => $totalAmount,
            ]);

            // Process payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $validated['amount_paid'],
                'change_amount' => max(0, $validated['amount_paid'] - $totalAmount),
                'cashier_id' => Auth::id(),
                'status' => 'completed',
                'transaction_id' => 'POS-' . time() . '-' . rand(1000, 9999),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order processed successfully',
                'order' => $order->load(['items.item', 'payment']),
                'payment' => $payment,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print receipt.
     */
    public function printReceipt($orderId)
    {
        $order = Order::with(['items.item', 'table', 'waiter', 'payment'])
            ->findOrFail($orderId);

        return view('pos.receipt', compact('order'));
    }
}
