<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SpecialSalesReportController extends Controller
{
    public function index(Request $request)
    {
        // Default to today's date
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));
        $orderType = $request->get('order_type');

        $minAllowedDate = '2026-01-01';

        // 1. Get latest 10 sales filtering by the given dates
        $lastTenSalesIds = collect();

        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate->lte($lastDate)) {

            $dailyLatestIds = Order::query()
                ->where('status', 'completed')
                ->where('is_paid', true)
                ->where('is_deleted', false)
                ->whereDate('completed_at', $currentDate->format('Y-m-d'))
                ->whereDate('completed_at', '>=', $minAllowedDate)
                ->when($orderType, fn($q) => $q->where('order_type', $orderType))
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->pluck('id');

            $lastTenSalesIds = $lastTenSalesIds->merge($dailyLatestIds);

            $currentDate->addDay();
        }

        $lastTenSalesIds = $lastTenSalesIds->unique()->values();

        // 2. Base query for paginated results
        $query = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false)
            ->whereDate('completed_at', '>=', $minAllowedDate);

        // Apply advanced filtering logic grouping
        $query->where(function ($q) use ($lastTenSalesIds) {
            $q->where(function ($sub) { // The conditional filter group
                $sub->whereRaw('id % 5 = 0')
                    ->orWhere(function ($vat) {
                        $vat->whereNotNull('customer_vat_number')
                            ->where('customer_vat_number', '!=', '');
                    })
                    ->orWhereHas('payment', function ($p) {
                        $p->whereIn('payment_method', ['card', 'mixed', 'credit'])
                            ->orWhere('card_amount', '>', 0)
                            ->orWhere('credit_amount', '>', 0);
                    });
            })
                ->orWhereIn('id', $lastTenSalesIds); // Always include these 10 
        });

        // Apply date filter
        $query->whereBetween('completed_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);

        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        // Get sales with pagination
        $orders = $query->with('payment')->orderBy('completed_at', 'desc')->paginate(100)->withQueryString();

        // 3. Totals query
        $allSalesQuery = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false)
            ->whereDate('completed_at', '>=', $minAllowedDate)
            ->where(function ($q) use ($lastTenSalesIds) {
                $q->where(function ($sub) {
                    $sub->whereRaw('id % 5 = 0')
                        ->orWhere(function ($vat) {
                            $vat->whereNotNull('customer_vat_number')
                                ->where('customer_vat_number', '!=', '');
                        })
                        ->orWhereHas('payment', function ($p) {
                            $p->whereIn('payment_method', ['card', 'mixed', 'credit'])
                                ->orWhere('card_amount', '>', 0)
                                ->orWhere('credit_amount', '>', 0);
                        });
                })
                    ->orWhereIn('id', $lastTenSalesIds);
            })
            ->whereBetween('completed_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->when($orderType, fn($q) => $q->where('order_type', $orderType));

        $allSales = $allSalesQuery->with('payment')->get();

        // Calculate totals with overpayment/priority trimming
        $totalGrandtotal = 0;
        $totalCash = 0;
        $totalCard = 0;
        $totalCredit = 0;

        foreach ($allSales as $sale) {
            $total = $sale->total_amount ?? 0;
            $totalGrandtotal += $total;

            if ($sale->payment) {
                // Fetch cash and card. Note: if change_amount exists, actual cash is cash - change.
                $customerPayment = max(0, ($sale->payment->cash_amount ?? 0) - ($sale->payment->change_amount ?? 0));
                $cardPayment = $sale->payment->card_amount ?? 0;
                $creditAmt = $sale->payment->credit_amount ?? 0;

                $totalCredit += $creditAmt;

                $paymentMethod = strtolower($sale->payment->payment_method);

                // Priority: Card first, then Cash
                if ($paymentMethod === 'cash') {
                    $totalCash += min($customerPayment, $total);
                } elseif ($paymentMethod === 'card') {
                    $totalCard += min($cardPayment, $total);
                } elseif ($paymentMethod === 'mixed') {
                    if ($cardPayment >= $total) {
                        $totalCard += $total;
                        // $totalCash is 0 in this case for the sum
                    } else {
                        $totalCard += $cardPayment;
                        $remaining = $total - $cardPayment;
                        $totalCash += min($customerPayment, $remaining);
                    }
                } elseif ($paymentMethod === 'credit') {
                    // Credit is already added above (or could be added specifically here)
                }
            }
        }

        $totals = (object) [
            'total_transactions' => $allSales->count(),
            'total_amount' => $totalGrandtotal,
            'total_cash' => $totalCash,
            'total_card' => $totalCard,
            'total_credit' => $totalCredit,
        ];

        return view('special-sales-report.index', compact('orders', 'totals', 'startDate', 'endDate', 'orderType'));
    }
}
