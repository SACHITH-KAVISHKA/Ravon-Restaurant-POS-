<?php

namespace App\Http\Controllers;

use App\Models\OrderItemPreparationLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDeliveredReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $orderTypes = $this->orderTypeOptions();

        $items = $this->buildItemPreparationQuery($filters)
            ->paginate(100)
            ->withQueryString();

        $summary = $this->buildItemSummary($filters);

        return view('super-admin-reports.order-delivered', compact(
            'items',
            'summary',
            'orderTypes',
            'filters'
        ));
    }

    public function itemDetails(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->hasAnyRole(['superadmin', 'manager']), 403);

        $filters = $this->resolveFilters($request);

        $itemId = $request->input('item_id');
        $itemModifierId = $request->input('item_modifier_id');

        abort_unless($itemId, 400, 'item_id is required');

        $statsQuery = OrderItemPreparationLog::query()
            ->join('orders', 'order_item_preparation_logs.order_id', '=', 'orders.id', 'inner', false)
            ->reportable()
            ->whereNotNull('order_item_preparation_logs.delivered_at')
            ->whereNotNull('order_item_preparation_logs.preparation_minutes')
            ->where('order_item_preparation_logs.item_id', $itemId)
            ->where('orders.is_deleted', false)
            ->whereNotIn('orders.status', ['cancelled', 'deleted'])
            ->whereBetween('orders.created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);

        if ($itemModifierId) {
            $statsQuery->where('order_item_preparation_logs.item_modifier_id', $itemModifierId);
        } else {
            $statsQuery->whereNull('order_item_preparation_logs.item_modifier_id');
        }

        if (!empty($filters['order_type'])) {
            $statsQuery->where('orders.order_type', $filters['order_type']);
        }

        $this->applyPaymentFilter($statsQuery, $filters['payment_status']);

        $stats = (clone $statsQuery)
            ->selectRaw('MAX(order_item_preparation_logs.item_display_name) as item_name')
            ->selectRaw('COUNT(DISTINCT order_item_preparation_logs.order_id) as times_ordered')
            ->selectRaw('COUNT(*) as total_quantity')
            ->selectRaw('ROUND(AVG(order_item_preparation_logs.preparation_minutes)) as avg_prep_time')
            ->selectRaw('MIN(order_item_preparation_logs.preparation_minutes) as min_prep_time')
            ->selectRaw('MAX(order_item_preparation_logs.preparation_minutes) as max_prep_time')
            ->first();

        $preparationRecords = (clone $statsQuery)
            ->select(
                'orders.order_number',
                'order_item_preparation_logs.item_display_name',
                'order_item_preparation_logs.prepared_at',
                'order_item_preparation_logs.delivered_at',
                'order_item_preparation_logs.preparation_minutes'
            )
            ->orderByDesc('order_item_preparation_logs.prepared_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'item' => [
                'name' => $stats->item_name ?? '-',
                'times_ordered' => (int) ($stats->times_ordered ?? 0),
                'total_quantity' => (int) ($stats->total_quantity ?? 0),
                'avg_prep_time' => (int) ($stats->avg_prep_time ?? 0),
                'min_prep_time' => (int) ($stats->min_prep_time ?? 0),
                'max_prep_time' => (int) ($stats->max_prep_time ?? 0),
            ],
            'preparation_records' => $preparationRecords->map(function ($record) {
                // prepared_at/delivered_at are already Eloquent datetime casts in the
                // app timezone (config('app.timezone')), so they can be formatted directly.
                $formatWithSeconds = 'Y-m-d H:i:s';

                return [
                    'order_number' => $record->order_number,
                    'item_name' => $record->item_display_name,
                    'prepared_at' => $record->prepared_at ? $record->prepared_at->format($formatWithSeconds) : '-',
                    'delivered_at' => $record->delivered_at ? $record->delivered_at->format($formatWithSeconds) : '-',
                    'kitchen_time' => (int) ($record->preparation_minutes ?? 0),
                ];
            })->values(),
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $items = $this->buildItemPreparationQuery($filters)->get();
        $summary = $this->buildItemSummary($filters);

        return view('super-admin-reports.order-delivered-print', compact('items', 'summary', 'filters'));
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $items = $this->buildItemPreparationQuery($filters)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Item Preparation Report');

        $headers = [
            'A1' => 'Item Name',
            'B1' => 'Times Ordered',
            'C1' => 'Total Quantity',
            'D1' => 'Avg Prep Time (min)',
            'E1' => 'Fastest Time (min)',
            'F1' => 'Slowest Time (min)',
            'G1' => 'Performance',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('764ba2');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        foreach ($items as $item) {
            $avgPrepTime = (int) ($item->avg_prep_time ?? 0);
            $performance = $avgPrepTime < 10 ? 'High' : ($avgPrepTime <= 20 ? 'Normal' : 'Slow');

            $sheet->setCellValue('A' . $row, $item->item_name);
            $sheet->setCellValue('B' . $row, (int) $item->times_ordered);
            $sheet->setCellValue('C' . $row, (int) $item->total_quantity);
            $sheet->setCellValue('D' . $row, $avgPrepTime);
            $sheet->setCellValue('E' . $row, (int) ($item->min_prep_time ?? 0));
            $sheet->setCellValue('F' . $row, (int) ($item->max_prep_time ?? 0));
            $sheet->setCellValue('G' . $row, $performance);

            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'item_preparation_report_' . $filters['start_date'] . '_to_' . $filters['end_date'] . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'order_type' => 'nullable|string',
            'payment_status' => 'nullable|in:all,paid,unpaid',
            'performance_check' => 'nullable|in:all,high,normal,slow',
        ]);

        return [
            'start_date' => Carbon::parse($validated['start_date'] ?? now()->toDateString())->toDateString(),
            'end_date' => Carbon::parse($validated['end_date'] ?? now()->toDateString())->toDateString(),
            'order_type' => $validated['order_type'] ?? '',
            'payment_status' => $validated['payment_status'] ?? 'all',
            'performance_check' => $validated['performance_check'] ?? 'all',
        ];
    }

    private function buildItemPreparationQuery(array $filters)
    {
        $query = OrderItemPreparationLog::query()
            ->join('orders', 'order_item_preparation_logs.order_id', '=', 'orders.id', 'inner', false)
            ->reportable()
            ->whereNotNull('order_item_preparation_logs.delivered_at')
            ->whereNotNull('order_item_preparation_logs.preparation_minutes')
            ->where('orders.is_deleted', false)
            ->whereNotIn('orders.status', ['cancelled', 'deleted'])
            ->whereBetween('orders.created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ])
            ->select(
                'order_item_preparation_logs.item_id',
                'order_item_preparation_logs.item_modifier_id',
                DB::raw('MAX(order_item_preparation_logs.item_display_name) as item_name'),
                DB::raw('COUNT(DISTINCT order_item_preparation_logs.order_id) as times_ordered'),
                DB::raw('COUNT(*) as total_quantity'),
                DB::raw('ROUND(AVG(order_item_preparation_logs.preparation_minutes)) as avg_prep_time'),
                DB::raw('MIN(order_item_preparation_logs.preparation_minutes) as min_prep_time'),
                DB::raw('MAX(order_item_preparation_logs.preparation_minutes) as max_prep_time')
            )
            ->groupBy('order_item_preparation_logs.item_id', 'order_item_preparation_logs.item_modifier_id')
            ->orderByDesc('avg_prep_time');

        if (!empty($filters['order_type'])) {
            $query->where('orders.order_type', $filters['order_type']);
        }

        $this->applyPaymentFilter($query, $filters['payment_status']);
        $this->applyPerformanceFilter($query, $filters['performance_check']);

        return $query;
    }

    private function buildItemSummary(array $filters): array
    {
        $items = $this->buildItemPreparationQuery($filters)->get();

        if ($items->isEmpty()) {
            return [
                'fastest_item' => ['name' => '-', 'time' => 0],
                'slowest_item' => ['name' => '-', 'time' => 0],
                'avg_kitchen_time' => 0,
                'total_items_prepared' => 0,
            ];
        }

        $fastest = $items->sortBy('avg_prep_time')->first();
        $slowest = $items->sortByDesc('avg_prep_time')->first();

        return [
            'fastest_item' => [
                'name' => $fastest->item_name ?? '-',
                'time' => (int) ($fastest->avg_prep_time ?? 0),
            ],
            'slowest_item' => [
                'name' => $slowest->item_name ?? '-',
                'time' => (int) ($slowest->avg_prep_time ?? 0),
            ],
            'avg_kitchen_time' => (int) round($items->avg('avg_prep_time')),
            'total_items_prepared' => (int) $items->sum('total_quantity'),
        ];
    }

    private function applyPaymentFilter($query, string $paymentStatus): void
    {
        if ($paymentStatus === 'paid') {
            $query->where(function ($q) {
                $q->where('orders.is_paid', true)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('payments')
                            ->whereColumn('payments.order_id', 'orders.id')
                            ->where('payments.payment_status', 'completed');
                    });
            });
        }

        if ($paymentStatus === 'unpaid') {
            $query->where(function ($q) {
                $q->where('orders.is_paid', false)
                    ->where(function ($sub) {
                        $sub->whereNotExists(function ($notExists) {
                            $notExists->select(DB::raw(1))
                                ->from('payments')
                                ->whereColumn('payments.order_id', 'orders.id')
                                ->where('payments.payment_status', 'completed');
                        })->orWhereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('payments')
                                ->whereColumn('payments.order_id', 'orders.id')
                                ->where('payments.payment_status', '!=', 'completed');
                        });
                    });
            });
        }
    }

    private function applyPerformanceFilter($query, string $performanceCheck): void
    {
        if ($performanceCheck === 'high') {
            $query->havingRaw('ROUND(AVG(order_item_preparation_logs.preparation_minutes)) < ?', [10]);
        }

        if ($performanceCheck === 'normal') {
            $query->havingRaw('ROUND(AVG(order_item_preparation_logs.preparation_minutes)) BETWEEN ? AND ?', [10, 20]);
        }

        if ($performanceCheck === 'slow') {
            $query->havingRaw('ROUND(AVG(order_item_preparation_logs.preparation_minutes)) > ?', [20]);
        }
    }

    private function orderTypeOptions(): array
    {
        return [
            'dine_in' => 'Dine In',
            'takeaway' => 'Take Away',
            'delivery' => 'Delivery',
            'uber_eats' => 'Uber Eats',
            'pickme' => 'PickMe',
        ];
    }
}
