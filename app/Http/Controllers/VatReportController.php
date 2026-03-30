<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VatReportController extends Controller
{
    /**
     * Display the VAT report — only orders with a VAT customer assigned.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::today()->format('Y-m-d'));
        $orderType = $request->get('order_type');

        $baseQuery = function () use ($startDate, $endDate, $orderType) {
            $q = Order::query()
                ->where('status', 'completed')
                ->where('is_paid', true)
                ->where('is_deleted', false)
                ->whereNotNull('customer_vat_number')
                ->where('customer_vat_number', '!=', '');

            if ($startDate) {
                $q->whereDate('completed_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('completed_at', '<=', $endDate);
            }
            if ($orderType) {
                $q->where('order_type', $orderType);
            }

            return $q;
        };

        // Paginated list
        $orders = $baseQuery()
            ->with(['payment.splits', 'waiter'])
            ->orderBy('completed_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        // All matching orders for totals
        $allOrders = $baseQuery()
            ->with('payment.splits')
            ->get();

        $totalCash   = 0;
        $totalCard   = 0;
        $totalCredit = 0;
        $totalVat    = 0;
        $totalSscl   = 0;
        $totalSubtotal = 0;

        foreach ($allOrders as $order) {
            $totalSubtotal += $order->subtotal ?? 0;
            $totalVat      += $order->vat_amount ?? 0;
            $totalSscl     += $order->sscl_amount ?? 0;

            if ($order->payment) {
                $cashAmt   = $order->payment->cash_amount   ?? 0;
                $changeAmt = $order->payment->change_amount ?? 0;
                $totalCash   += max(0, $cashAmt - $changeAmt);
                $totalCard   += $order->payment->card_amount   ?? 0;
                $totalCredit += $order->payment->credit_amount ?? 0;
            }
        }

        $totals = (object) [
            'total_transactions' => $allOrders->count(),
            'total_subtotal'     => $totalSubtotal,
            'total_vat'          => $totalVat,
            'total_sscl'         => $totalSscl,
            'total_amount'       => $allOrders->sum('total_amount'),
            'total_cash'         => $totalCash,
            'total_card'         => $totalCard,
            'total_credit'       => $totalCredit,
        ];

        return view('vat-report.index', compact(
            'orders',
            'totals',
            'startDate',
            'endDate',
            'orderType'
        ));
    }

    /**
     * Get sale details (VAT view) for AJAX modal — delegates to SalesReportController.
     */
    public function getSaleDetails(Order $order)
    {
        return app(SalesReportController::class)->getSaleDetails($order);
    }

    /**
     * Export VAT report to Excel.
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::today()->format('Y-m-d'));
        $orderType = $request->get('order_type');

        $query = Order::query()
            ->where('status', 'completed')
            ->where('is_paid', true)
            ->where('is_deleted', false)
            ->whereNotNull('customer_vat_number')
            ->where('customer_vat_number', '!=', '');

        if ($startDate) {
            $query->whereDate('completed_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('completed_at', '<=', $endDate);
        }
        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        $orders = $query->with(['payment.splits', 'waiter'])
            ->orderBy('completed_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('VAT Report');

        $headers = [
            'A1' => 'Order Number',
            'B1' => 'Customer Name',
            'C1' => 'VAT Number',
            'D1' => 'Order Type',
            'E1' => 'Payment Method',
            'F1' => 'Subtotal',
            'G1' => 'VAT Amount',
            'H1' => 'SSCL Amount',
            'I1' => 'Total',
            'J1' => 'Cash',
            'K1' => 'Card',
            'L1' => 'Credit',
            'M1' => 'Date & Time',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1d7cf2');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        $totalAmount = 0;
        $totalCash   = 0;
        $totalCard   = 0;
        $totalCredit = 0;
        $totalVat    = 0;
        $totalSscl   = 0;
        $totalSubtotal = 0;

        foreach ($orders as $order) {
            $cashAmount   = $order->payment ? $order->payment->cash_amount   : 0;
            $cardAmount   = $order->payment ? $order->payment->card_amount   : 0;
            $creditAmount = $order->payment ? $order->payment->credit_amount : 0;
            $changeAmount = $order->payment ? $order->payment->change_amount : 0;
            $netCash      = max(0, $cashAmount - $changeAmount);

            $sheet->setCellValue('A' . $row, $order->order_number);
            $sheet->setCellValue('B' . $row, $order->customer_name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $order->customer_vat_number ?? 'N/A');
            $sheet->setCellValue('D' . $row, ucfirst(str_replace('_', ' ', $order->order_type)));
            $sheet->setCellValue('E' . $row, $order->payment ? $order->payment->payment_method : 'N/A');
            $sheet->setCellValue('F' . $row, $order->subtotal ?? 0);
            $sheet->setCellValue('G' . $row, $order->vat_amount ?? 0);
            $sheet->setCellValue('H' . $row, $order->sscl_amount ?? 0);
            $sheet->setCellValue('I' . $row, $order->total_amount);
            $sheet->setCellValue('J' . $row, $netCash);
            $sheet->setCellValue('K' . $row, $cardAmount);
            $sheet->setCellValue('L' . $row, $creditAmount);
            $sheet->setCellValue('M' . $row, $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : 'N/A');

            $totalAmount   += $order->total_amount;
            $totalSubtotal += $order->subtotal ?? 0;
            $totalVat      += $order->vat_amount ?? 0;
            $totalSscl     += $order->sscl_amount ?? 0;
            $totalCash     += $netCash;
            $totalCard     += $cardAmount;
            $totalCredit   += $creditAmount;

            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, $totalSubtotal);
        $sheet->setCellValue('G' . $row, $totalVat);
        $sheet->setCellValue('H' . $row, $totalSscl);
        $sheet->setCellValue('I' . $row, $totalAmount);
        $sheet->setCellValue('J' . $row, $totalCash);
        $sheet->setCellValue('K' . $row, $totalCard);
        $sheet->setCellValue('L' . $row, $totalCredit);

        $sheet->getStyle('A' . $row . ':M' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':M' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E3F2FD');

        foreach (['F', 'G', 'H', 'I', 'J', 'K', 'L'] as $column) {
            $sheet->getStyle($column . '2:' . $column . $row)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'vat_report_' . $startDate . '_to_' . $endDate . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
