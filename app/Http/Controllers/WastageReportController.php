<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\MainStockItem;
use Illuminate\Http\Request;

class WastageReportController extends Controller
{
    /**
     * Show the admin wastage report page.
     */
    public function index()
    {
        $items = MainStockItem::where('is_active', true)
            ->orderBy('item_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                ];
            })
            ->values();

        $reasons = Wastage::REASONS;

        return view('reports.wastage', compact('items', 'reasons'));
    }

    /**
     * Get filtered wastage data via AJAX.
     */
    public function getData(Request $request)
    {
        $query = Wastage::with(['mainStockItem', 'performer'])
            ->orderBy('wastage_date', 'desc');

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('main_stock_item_id', $request->item_id);
        }

        // Filter by item type
        if ($request->filled('item_type') && $request->item_type !== 'all') {
            $query->where('item_type', $request->item_type);
        }

        // Filter by reason
        if ($request->filled('reason') && $request->reason !== 'all') {
            $query->where('reason', $request->reason);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('wastage_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('wastage_date', '<=', $request->to_date);
        }

        $wastages = $query->limit(500)->get();

        $data = $wastages->map(function ($w) {
            return [
                'id' => $w->id,
                'wastage_id' => $w->wastage_id,
                'date_time' => $w->wastage_date->format('Y-m-d H:i:s'),
                'date_display' => $w->wastage_date->format('d M Y h:i A'),
                'item_name' => $w->item_name,
                'item_code' => $w->item_code ?? '-',
                'item_type' => $w->item_type,
                'item_type_label' => $w->item_type === 'finished_good' ? 'FG' : 'RM',
                'quantity_before' => number_format($w->quantity_before, 3),
                'quantity_wasted' => number_format($w->quantity_wasted, 3),
                'quantity_after' => number_format($w->quantity_after, 3),
                'unit' => $w->unit,
                'price' => number_format($w->price, 2),
                'wastage_amount' => number_format($w->wastage_amount, 2),
                'wastage_amount_raw' => (float) $w->wastage_amount,
                'reason' => $w->reason,
                'reason_label' => $w->reason_label,
                'notes' => $w->notes ?? '-',
                'performed_by' => $w->performer->name ?? 'Unknown',
            ];
        });

        // Summary stats
        $totalWastageAmount = $wastages->sum('wastage_amount');
        $totalItems = $wastages->count();
        $fgCount = $wastages->where('item_type', 'finished_good')->count();
        $rmCount = $wastages->where('item_type', 'raw_material')->count();
        $fgAmount = $wastages->where('item_type', 'finished_good')->sum('wastage_amount');
        $rmAmount = $wastages->where('item_type', 'raw_material')->sum('wastage_amount');

        // Top wasted items
        $topItems = $wastages->groupBy('main_stock_item_id')->map(function ($group) {
            return [
                'item_name' => $group->first()->item_name,
                'item_code' => $group->first()->item_code,
                'total_qty' => $group->sum('quantity_wasted'),
                'total_amount' => $group->sum('wastage_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total_amount')->take(10)->values();

        // By reason breakdown
        $byReason = $wastages->groupBy('reason')->map(function ($group, $reason) {
            $reasons = Wastage::REASONS;
            return [
                'reason' => $reason,
                'reason_label' => $reasons[$reason] ?? $reason,
                'count' => $group->count(),
                'total_amount' => $group->sum('wastage_amount'),
            ];
        })->sortByDesc('total_amount')->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total_amount' => number_format($totalWastageAmount, 2),
                'total_amount_raw' => $totalWastageAmount,
                'total_items' => $totalItems,
                'fg_count' => $fgCount,
                'rm_count' => $rmCount,
                'fg_amount' => number_format($fgAmount, 2),
                'rm_amount' => number_format($rmAmount, 2),
            ],
            'top_items' => $topItems,
            'by_reason' => $byReason,
            'total' => $data->count(),
        ]);
    }
}
