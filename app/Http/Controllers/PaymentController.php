<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $orders = Order::with(['table', 'waiter', 'items.item'])
            ->whereIn('status', ['pending', 'served'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('payments.index', compact('orders'));
    }

    public function show($orderId)
    {
        $order = Order::with(['table', 'waiter', 'items.item'])
            ->findOrFail($orderId);
        
        return view('payments.show', compact('order'));
    }

    public function process(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,upi,other',
            'amount_received' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with('table')->findOrFail($orderId);
            
            if ($order->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid'
                ], 400);
            }

            $changeAmount = max(0, $validated['amount_received'] - $order->total_amount);

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount ?? 0,
                'total_amount' => $order->total_amount,
                'amount_received' => $validated['amount_received'],
                'change_amount' => $changeAmount,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Update order status
            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            // Free up the table
            if ($order->table) {
                $order->table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment' => $payment,
                'change_amount' => $changeAmount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
