<?php

namespace App\Http\Controllers;

use App\Models\CashierFgStockLog;
use App\Models\MainStockItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemTransactionReportController extends Controller
{
    /**
     * Show item transaction report page.
     */
    public function index()
    {
        $branches = User::query()
            ->role('cashier')
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $items = MainStockItem::query()
            ->active()
            ->ofType('finished_good')
            ->orderBy('item_name')
            ->get(['id', 'item_code', 'item_name']);

        return view('reports.item-transactions', compact('branches', 'items'));
    }

    /**
     * Return filtered item transaction history data.
     */
    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:users,id',
            'item_id' => 'required|exists:main_stock_items,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $start = Carbon::parse($validated['from_date'])->startOfDay();
        $end = Carbon::parse($validated['to_date'])->endOfDay();

        $item = MainStockItem::query()->findOrFail($validated['item_id']);
        $branch = null;

        $baseQuery = CashierFgStockLog::query()
            ->with('performer:id,name')
            ->where('main_stock_item_id', $validated['item_id']);

        if (!empty($validated['branch_id'])) {
            $branch = User::query()->find($validated['branch_id']);
            $baseQuery->where('performed_by', $validated['branch_id']);
        }

        $openingLog = (clone $baseQuery)
            ->where('created_at', '<', $start)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $openingBalance = $openingLog ? (float) $openingLog->quantity_after : 0.0;

        $transactions = (clone $baseQuery)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $rows = $transactions->map(function (CashierFgStockLog $log) {
            $changed = (float) $log->quantity_changed;

            return [
                'date_time' => $log->created_at?->format('Y-m-d H:i') ?? '-',
                'type' => $log->action_label,
                'type_key' => $log->action_type,
                'reference' => $log->reference_id ?: '-',
                'performed_by' => $log->performer->name ?? '-',
                'quantity_changed' => $changed,
                'quantity_display' => $this->formatQuantitySigned($changed),
            ];
        })->values();

        $closingBalance = $transactions->isNotEmpty()
            ? (float) $transactions->last()->quantity_after
            : $openingBalance;

        return response()->json([
            'success' => true,
            'meta' => [
                'item_name' => $item->item_name,
                'item_code' => $item->item_code,
                'unit' => $item->unit_abbreviation,
                'branch_name' => $branch?->name,
                'from_date' => $start->toDateString(),
                'to_date' => $end->toDateString(),
                'opening_balance' => $openingBalance,
                'opening_display' => $this->formatQuantity($openingBalance),
                'closing_balance' => $closingBalance,
                'closing_display' => $this->formatQuantity($closingBalance),
            ],
            'transactions' => $rows,
        ]);
    }

    /**
     * Format quantity with up to 3 decimals and no trailing zeros.
     */
    private function formatQuantity(float $value): string
    {
        $formatted = number_format($value, 3, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Format signed quantity with + or - prefix.
     */
    private function formatQuantitySigned(float $value): string
    {
        $formatted = $this->formatQuantity(abs($value));

        if ($value > 0) {
            return '+' . $formatted;
        }

        if ($value < 0) {
            return '-' . $formatted;
        }

        return '0';
    }
}
