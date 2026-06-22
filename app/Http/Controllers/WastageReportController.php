<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\MainStockItem;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $wastages = $this->buildFilteredQuery($request)->limit(500)->get();

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

    /**
     * Export filtered wastage data as a real Excel workbook.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $wastages = $this->buildFilteredQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wastage Report');

        $headers = [
            'A1' => '#',
            'B1' => 'Date & Time',
            'C1' => 'Wastage ID',
            'D1' => 'Item Code',
            'E1' => 'Item Name',
            'F1' => 'Type',
            'G1' => 'Qty Before',
            'H1' => 'Qty Wasted',
            'I1' => 'Qty After',
            'J1' => 'Unit',
            'K1' => 'Price',
            'L1' => 'Amount',
            'M1' => 'Reason',
            'N1' => 'Notes',
            'O1' => 'Cashier',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('dc2626');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        foreach ($wastages as $index => $wastage) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $wastage->wastage_date?->format('Y-m-d H:i:s'));
            $sheet->setCellValue('C' . $row, $wastage->wastage_id);
            $sheet->setCellValue('D' . $row, $wastage->item_code ?? '-');
            $sheet->setCellValue('E' . $row, $wastage->item_name);
            $sheet->setCellValue('F' . $row, $wastage->item_type === 'finished_good' ? 'FG' : 'RM');
            $sheet->setCellValue('G' . $row, (float) $wastage->quantity_before);
            $sheet->setCellValue('H' . $row, (float) $wastage->quantity_wasted);
            $sheet->setCellValue('I' . $row, (float) $wastage->quantity_after);
            $sheet->setCellValue('J' . $row, $wastage->unit);
            $sheet->setCellValue('K' . $row, (float) $wastage->price);
            $sheet->setCellValue('L' . $row, (float) $wastage->wastage_amount);
            $sheet->setCellValue('M' . $row, $wastage->reason_label);
            $sheet->setCellValue('N' . $row, $wastage->notes ?? '-');
            $sheet->setCellValue('O' . $row, $wastage->performer->name ?? 'Unknown');
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        $sheet->mergeCells('A' . $totalRow . ':K' . $totalRow);
        $sheet->setCellValue('L' . $totalRow, (float) $wastages->sum('wastage_amount'));
        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('fee2e2');

        $lastDataRow = max($row - 1, 2);
        foreach (['G', 'H', 'I'] as $column) {
            $sheet->getStyle($column . '2:' . $column . $lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.000');
        }

        foreach (['K', 'L'] as $column) {
            $sheet->getStyle($column . '2:' . $column . $totalRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $filename = 'wastage_report_' . $fromDate . '_to_' . $toDate . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = Wastage::with(['mainStockItem', 'performer'])
            ->orderBy('wastage_date', 'desc');

        if ($request->filled('item_id')) {
            $query->where('main_stock_item_id', $request->item_id);
        }

        if ($request->filled('item_type') && $request->item_type !== 'all') {
            $query->where('item_type', $request->item_type);
        }

        if ($request->filled('reason') && $request->reason !== 'all') {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('wastage_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('wastage_date', '<=', $request->to_date);
        }

        return $query;
    }
}
