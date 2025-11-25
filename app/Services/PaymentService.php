<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSplit;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    /**
     * Process payment for an order.
     */
    public function processPayment(Order $order, array $paymentData): Payment
    {
        DB::beginTransaction();
        try {
            // Validate order can be paid
            if ($order->isPaid()) {
                throw new Exception('Order already paid');
            }

            if ($order->status === 'cancelled') {
                throw new Exception('Cannot pay for cancelled order');
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'paid_amount' => $paymentData['paid_amount'],
                'change_amount' => max(0, $paymentData['paid_amount'] - $order->total_amount),
                'payment_method' => $paymentData['payment_method'],
                'payment_status' => 'completed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'reference_number' => $paymentData['reference_number'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Handle split payments
            if ($paymentData['payment_method'] === 'mixed' && isset($paymentData['splits'])) {
                foreach ($paymentData['splits'] as $split) {
                    PaymentSplit::create([
                        'payment_id' => $payment->id,
                        'payment_method' => $split['method'],
                        'amount' => $split['amount'],
                        'reference_number' => $split['reference_number'] ?? null,
                    ]);
                }
            }

            // Update order status
            $order->update(['status' => 'completed', 'completed_at' => now()]);

            // Update table if dine-in
            if ($order->table) {
                $order->table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            DB::commit();

            // Log audit
            \App\Models\AuditLog::logAction('payment_processed', $payment, [], [
                'order_id' => $order->id,
                'amount' => $payment->total_amount,
            ]);

            // Broadcast
            broadcast(new \App\Events\PaymentProcessed($payment));

            return $payment->fresh(['order', 'splits']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process refund.
     */
    public function processRefund(Payment $payment, string $reason): Payment
    {
        DB::beginTransaction();
        try {
            if ($payment->payment_status === 'refunded') {
                throw new Exception('Payment already refunded');
            }

            $payment->update([
                'payment_status' => 'refunded',
                'notes' => ($payment->notes ?? '') . "\nRefund Reason: " . $reason,
            ]);

            // Update order
            $payment->order->update(['status' => 'cancelled']);

            DB::commit();

            // Log audit
            \App\Models\AuditLog::logAction('payment_refunded', $payment, [], [
                'reason' => $reason,
            ]);

            return $payment->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate bill/receipt data.
     */
    public function generateBillData(Order $order): array
    {
        return [
            // Header
            'restaurant_name' => config('app.restaurant_name', 'Ravon Restaurant'),
            'restaurant_address' => config('app.restaurant_address'),
            'restaurant_phone' => config('app.restaurant_phone'),
            'restaurant_email' => config('app.restaurant_email'),
            
            // Order Info
            'order_number' => $order->order_number,
            'order_date' => $order->created_at->format('d/m/Y H:i'),
            'order_type' => strtoupper(str_replace('_', ' ', $order->order_type)),
            'table' => $order->table?->table_number ?? 'N/A',
            'waiter' => $order->waiter?->name ?? 'N/A',
            'guest_count' => $order->guest_count,
            
            // Items
            'items' => $order->orderItems->map(function ($item) {
                $modifiers = $item->modifiers->map(function ($mod) {
                    return [
                        'name' => $mod->modifier->name,
                        'price' => $mod->price_adjustment,
                    ];
                })->toArray();

                return [
                    'name' => $item->item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'modifiers' => $modifiers,
                    'subtotal' => $item->subtotal,
                ];
            })->toArray(),
            
            // Totals
            'subtotal' => $order->subtotal,
            'discount' => $order->discount_amount,
            'service_charge' => $order->service_charge,
            'tax' => $order->tax_amount,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total_amount,
            
            // Payment
            'payment_method' => $order->payment?->payment_method,
            'paid_amount' => $order->payment?->paid_amount,
            'change' => $order->payment?->change_amount,
            
            // Footer
            'footer_message' => config('app.bill_footer', 'Thank you for dining with us!'),
            'printed_at' => now()->format('d/m/Y H:i:s'),
            'printed_by' => auth()->user()->name,
        ];
    }

    /**
     * Print bill.
     */
    public function printBill(Order $order): bool
    {
        try {
            $billData = $this->generateBillData($order);
            
            // Send to printer
            return app(PrinterService::class)->printBill($billData);

        } catch (Exception $e) {
            logger()->error('Bill Print Failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get daily payment summary.
     */
    public function getDailySummary($date = null)
    {
        $date = $date ?? today();

        $payments = Payment::whereDate('processed_at', $date)
            ->where('payment_status', 'completed')
            ->get();

        return [
            'total_transactions' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'cash_amount' => $payments->where('payment_method', 'cash')->sum('paid_amount'),
            'card_amount' => $payments->where('payment_method', 'card')->sum('paid_amount'),
            'mixed_amount' => $payments->where('payment_method', 'mixed')->sum('paid_amount'),
            'average_transaction' => $payments->avg('total_amount'),
        ];
    }

    /**
     * Get payment statistics.
     */
    public function getPaymentStatistics($startDate, $endDate)
    {
        return Payment::whereBetween('processed_at', [$startDate, $endDate])
            ->where('payment_status', 'completed')
            ->select(
                DB::raw('DATE(processed_at) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(CASE WHEN payment_method = "cash" THEN paid_amount ELSE 0 END) as cash_sales'),
                DB::raw('SUM(CASE WHEN payment_method = "card" THEN paid_amount ELSE 0 END) as card_sales')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
