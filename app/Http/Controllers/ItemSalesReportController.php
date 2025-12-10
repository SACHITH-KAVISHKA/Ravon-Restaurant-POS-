<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ItemSalesReportController extends Controller
{
    /**
     * Display the item sales summary report
     */
    public function index(Request $request)
    {
        return view('reports.item-sales');
    }

    /**
     * Filter item sales data via AJAX
     */
    public function filter(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $fromDate = $request->start_date;
        $toDate = $request->end_date;

        $salesData = $this->getSalesData($fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => $salesData,
            'summary' => [
                'total_quantity' => array_sum(array_column($salesData, 'total_quantity')),
                'unique_items' => count($salesData)
            ]
        ]);
    }

    /**
     * Get detailed transactions for a specific item
     */
    public function getItemDetails(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $itemId = $request->item_id;
        $fromDate = $request->start_date;
        $toDate = $request->end_date;

        $item = Item::findOrFail($itemId);

        $transactions = OrderItem::with(['order' => function ($query) {
            $query->select('id', 'order_number', 'completed_at');
        }])
            ->whereHas('order', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'completed')
                    ->where('is_paid', true)
                    ->whereBetween(DB::raw('DATE(completed_at)'), [$fromDate, $toDate]);
            })
            ->where('item_id', $itemId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(function ($orderItem) {
                return [
                    'order_number' => $orderItem->order->order_number ?? 'N/A',
                    'quantity' => (int)$orderItem->quantity,
                    'unit_price' => (float)$orderItem->unit_price,
                    'subtotal' => (float)$orderItem->subtotal,
                    'completed_at' => $orderItem->order->completed_at ?
                        $orderItem->order->completed_at->format('Y-m-d H:i:s') : 'N/A'
                ];
            });

        $totalQuantity = $transactions->sum('quantity');

        return response()->json([
            'success' => true,
            'item' => [
                'code' => $item->item_code ?? $item->id,
                'name' => $item->name,
            ],
            'transactions' => $transactions,
            'total_quantity' => $totalQuantity
        ]);
    }

    /**
     * Export summary report to Excel
     */
    public function exportSummary(Request $request)
    {
        $fromDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $toDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $salesData = $this->getSalesData($fromDate, $toDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'RAVON RESTAURANT - Item Sales Summary Report');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Period
        $sheet->setCellValue('A2', "Period: $fromDate to $toDate");
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $headerRow = 4;
        $sheet->setCellValue('A' . $headerRow, 'Item Code');
        $sheet->setCellValue('B' . $headerRow, 'Item Name');
        $sheet->setCellValue('C' . $headerRow, 'Total Qty');

        // Style headers
        $sheet->getStyle('A' . $headerRow . ':C' . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow . ':C' . $headerRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Data rows
        $row = $headerRow + 1;
        foreach ($salesData as $item) {
            $sheet->setCellValue('A' . $row, $item['item_code']);
            $sheet->setCellValue('B' . $row, $item['item_name']);
            $sheet->setCellValue('C' . $row, $item['total_quantity']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'C') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Download
        $filename = 'item_sales_summary_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export item detail transactions to Excel
     */
    public function exportItemDetails(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $itemId = $request->item_id;
        $fromDate = $request->start_date;
        $toDate = $request->end_date;

        $item = Item::findOrFail($itemId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $itemCode = $item->item_code ?? $item->id;
        $sheet->setCellValue('A1', $item->name . ' (Code: ' . $itemCode . ') - Transaction Details');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', "Period: $fromDate to $toDate");
        $sheet->mergeCells('A2:E2');

        $row = 4;

        // Column headers
        $sheet->setCellValue('A' . $row, 'Order Number');
        $sheet->setCellValue('B' . $row, 'Quantity');
        $sheet->setCellValue('C' . $row, 'Unit Price');
        $sheet->setCellValue('D' . $row, 'Total Price');
        $sheet->setCellValue('E' . $row, 'Date');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $row++;

        // Get transactions
        $transactions = OrderItem::with(['order'])
            ->whereHas('order', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'completed')
                    ->where('is_paid', true)
                    ->whereBetween(DB::raw('DATE(completed_at)'), [$fromDate, $toDate]);
            })
            ->where('item_id', $itemId)
            ->where('status', '!=', 'cancelled')
            ->get();

        // Transaction rows
        $totalQty = 0;
        foreach ($transactions as $transaction) {
            $sheet->setCellValue('A' . $row, $transaction->order->order_number);
            $sheet->setCellValue('B' . $row, $transaction->quantity);
            $sheet->setCellValue('C' . $row, $transaction->unit_price);
            $sheet->setCellValue('D' . $row, $transaction->subtotal);
            $sheet->setCellValue(
                'E' . $row,
                $transaction->order->completed_at ?
                    $transaction->order->completed_at->format('Y-m-d H:i') : 'N/A'
            );
            $totalQty += $transaction->quantity;
            $row++;
        }

        // Total
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTAL:');
        $sheet->setCellValue('B' . $row, $totalQty);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(12);

        // Auto-size columns
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Download
        $filename = 'item_details_' . $itemCode . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Get aggregated sales data for all items
     */
    private function getSalesData($fromDate, $toDate)
    {
        // Get items that have sales in the date range
        $salesData = OrderItem::with('item')
            ->select('item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereHas('order', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'completed')
                    ->where('is_paid', true)
                    ->whereBetween(DB::raw('DATE(completed_at)'), [$fromDate, $toDate]);
            })
            ->where('status', '!=', 'cancelled')
            ->groupBy('item_id')
            ->get()
            ->map(function ($orderItem) {
                $item = $orderItem->item;
                return [
                    'item_id' => $item->id,
                    'item_code' => $item->id, // Using ID as code since item_code doesn't exist
                    'item_name' => $item->name,
                    'total_quantity' => (int)$orderItem->total_quantity
                ];
            })
            ->sortBy('item_name')
            ->values()
            ->toArray();

        return $salesData;
    }
}
