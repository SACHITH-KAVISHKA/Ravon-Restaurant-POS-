<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Process payment for an order
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cash,card,mobile,online',
            'amount_received' => 'required|numeric|min:0',
            'splits' => 'nullable|array',
            'splits.*.payment_method' => 'required|in:cash,card,mobile,online',
            'splits.*.amount' => 'required|numeric|min:0',
            'splits.*.reference_number' => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Process payment
        $payment = $this->paymentService->processPayment(
            $order,
            $request->payment_method,
            $request->amount_received,
            $request->splits ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment->load(['order', 'splits']),
        ], 201);
    }

    /**
     * Get payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['order.table', 'order.items.item', 'cashier', 'splits']);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Get bill data for an order
     */
    public function getBill(Order $order)
    {
        $billData = $this->paymentService->generateBillData($order);

        return response()->json([
            'success' => true,
            'data' => $billData,
        ]);
    }

    /**
     * Print bill for an order
     */
    public function printBill(Order $order)
    {
        $result = $this->paymentService->printBill($order);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Bill printed successfully' : 'Failed to print bill',
        ]);
    }

    /**
     * Process refund
     */
    public function refund(Request $request, Payment $payment)
    {
        $this->authorize('refund-payments');

        $request->validate([
            'refund_amount' => 'required|numeric|min:0|max:' . $payment->total_amount,
            'refund_reason' => 'required|string',
        ]);

        $refund = $this->paymentService->processRefund(
            $payment,
            $request->refund_amount,
            $request->refund_reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully',
            'data' => $refund,
        ]);
    }

    /**
     * Get daily payment summary
     */
    public function dailySummary(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $summary = $this->paymentService->getDailySummary($date);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $stats = $this->paymentService->getPaymentStatistics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * List all payments with filters
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order', 'cashier']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by cashier
        if ($request->has('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }

        $payments = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
