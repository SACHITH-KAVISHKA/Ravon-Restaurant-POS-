<?php

namespace App\Http\Controllers\Admin\Analysis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LastOrderDateController extends Controller
{
    /**
     * Display the Last Order Date Report.
     *
     * Logic:
     *   - Items WITHOUT modifiers → show the base item row.
     *   - Items WITH modifiers   → show each modifier/portion as a separate row
     *     (the base item itself is NOT shown).
     *   - Uses item_display_name from order_items for the POS display name.
     *   - Only completed/paid orders with non-deleted order items.
     */
    public function index(Request $request)
    {
        $search    = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $query = $this->buildReportQuery($search, $startDate, $endDate);

        $items = $query->paginate(50)->appends($request->query());

        return view('admin.reports.analysis.last-order-date', compact('items', 'search', 'startDate', 'endDate'));
    }

    /**
     * Export the filtered report data as an Excel file.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $search    = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $query = $this->buildReportQuery($search, $startDate, $endDate);
        $items = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Last Order Date Report');

        // Title row
        $sheet->setCellValue('A1', 'RAVON RESTAURANT - Last Order Date Report');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Period row
        $periodText = 'All Time';
        if ($startDate && $endDate) {
            $periodText = "Period: $startDate to $endDate";
        } elseif ($startDate) {
            $periodText = "From: $startDate";
        } elseif ($endDate) {
            $periodText = "Until: $endDate";
        }
        if ($search) {
            $periodText .= " | Search: $search";
        }
        $sheet->setCellValue('A2', $periodText);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row
        $headerRow = 4;
        $headers = [
            'A' => '#',
            'B' => 'Item Name',
            'C' => 'Category',
            'D' => 'Last Sold Date',
            'E' => 'Last Order No',
            'F' => 'Quantity Sold',
            'G' => 'Days Since Last Sale',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('667eea');
        }

        // Data rows
        $row = $headerRow + 1;
        foreach ($items as $index => $item) {
            $daysSince = '-';
            if ($item->last_order_date) {
                $daysSince = (int) Carbon::parse($item->last_order_date)->diffInDays(now());
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->display_name);
            $sheet->setCellValue('C' . $row, $item->category_name);
            $sheet->setCellValue('D' . $row, $item->last_order_date
                ? Carbon::parse($item->last_order_date)->format('Y-m-d H:i')
                : '-');
            $sheet->setCellValue('E' . $row, $item->last_order_number ?? '-');
            $sheet->setCellValue('F' . $row, $item->last_order_date ? $item->quantity_sold : 0);
            $sheet->setCellValue('G' . $row, $daysSince);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'last_order_date_report_' . date('Y-m-d_His') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Build the report query, shared between index() and exportExcel().
     *
     * Creates a "catalog" of sellable units:
     *   Part A – Items that have NO modifiers  (show base item)
     *   Part B – Each modifier row for items that HAVE modifiers  (show portion)
     *
     * Then left-joins to find the latest completed/paid order for each unit.
     */
    private function buildReportQuery(?string $search, ?string $startDate, ?string $endDate)
    {
        /*
         * Part A: Items WITHOUT any modifiers.
         */
        $itemsWithoutModifiers = DB::table('items')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->where('items.status', 1)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('item_modifiers')
                    ->whereColumn('item_modifiers.item_id', 'items.id');
            })
            ->select([
                'items.id as item_id',
                DB::raw('CAST(NULL AS UNSIGNED) as modifier_id'),
                'items.name as display_name',
                'categories.name as category_name',
            ]);

        /*
         * Part B: Each modifier for items that HAVE modifiers.
         */
        $itemsWithModifiers = DB::table('items')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('item_modifiers', 'item_modifiers.item_id', '=', 'items.id')
            ->where('items.status', 1)
            ->where('item_modifiers.status', 1)
            ->select([
                'items.id as item_id',
                'item_modifiers.id as modifier_id',
                DB::raw("CONCAT(items.name, ' - ', item_modifiers.name) as display_name"),
                'categories.name as category_name',
            ]);

        /*
         * Union both into a derived "catalog" table.
         */
        $catalog = $itemsWithoutModifiers->unionAll($itemsWithModifiers);

        /*
         * Subquery: latest order_id per (item_id, item_modifier_id) among
         * completed + paid orders with non-deleted order items.
         */
        $latestOrderSub = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->where('orders.is_paid', true)
            ->where('order_items.status', '!=', 'deleted');

        // Apply date range filter to the subquery
        if ($startDate) {
            $latestOrderSub->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $latestOrderSub->whereDate('orders.created_at', '<=', $endDate);
        }

        $latestOrderSub->select([
                'order_items.item_id',
                'order_items.item_modifier_id',
                DB::raw('MAX(orders.id) as latest_order_id'),
            ])
            ->groupBy('order_items.item_id', 'order_items.item_modifier_id');

        /*
         * Main query: catalog → latest_sale → orders → order_items
         */
        $query = DB::query()
            ->fromSub($catalog, 'catalog')
            ->select([
                'catalog.item_id',
                'catalog.modifier_id',
                'catalog.display_name',
                'catalog.category_name',
                'orders.created_at as last_order_date',
                'orders.order_number as last_order_number',
                'order_items.quantity as quantity_sold',
            ])
            ->leftJoinSub($latestOrderSub, 'latest_sale', function ($join) {
                $join->on('catalog.item_id', '=', 'latest_sale.item_id')
                    ->whereRaw('COALESCE(catalog.modifier_id, 0) = COALESCE(latest_sale.item_modifier_id, 0)');
            })
            ->leftJoin('orders', 'orders.id', '=', 'latest_sale.latest_order_id')
            ->leftJoin('order_items', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->on('order_items.item_id', '=', 'catalog.item_id')
                    ->whereRaw('COALESCE(order_items.item_modifier_id, 0) = COALESCE(catalog.modifier_id, 0)')
                    ->where('order_items.status', '!=', 'deleted');
            });

        // Search by display name (item name or "item - portion")
        if ($search) {
            $query->where('catalog.display_name', 'like', '%' . $search . '%');
        }

        // Sort: latest sold items first, never-sold items at the bottom
        $query->orderByRaw('orders.created_at IS NULL, orders.created_at DESC');

        return $query;
    }
}
