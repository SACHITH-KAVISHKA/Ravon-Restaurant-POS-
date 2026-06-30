<?php

namespace App\Http\Controllers;

use App\Models\PriceListActivity;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PriceListActivityController extends Controller
{
    /**
     * Display a listing of the price list activities.
     */
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();
        $query = $this->buildFilterQuery($request);

        $activities = $query->paginate(50)->withQueryString();

        return view('menu.price-list-activity', compact('activities', 'users'));
    }

    /**
     * Export the filtered price list activities to Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $query = $this->buildFilterQuery($request);
        $activities = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Price List Activity');

        $headers = [
            'A1' => 'Date & Time',
            'B1' => 'Item Name',
            'C1' => 'Portion',
            'D1' => 'Previous Price',
            'E1' => 'New Price',
            'F1' => 'Changed By',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('764ba2'); // Purple color matching the POS theme
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        foreach ($activities as $activity) {
            $sheet->setCellValue('A' . $row, $activity->created_at ? $activity->created_at->format('Y-m-d H:i:s') : 'N/A');
            $sheet->setCellValue('B' . $row, $activity->item?->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $activity->portion?->name ?? 'Main Item');
            $sheet->setCellValue('D' . $row, (float) $activity->previous_price);
            $sheet->setCellValue('E' . $row, (float) $activity->new_price);
            $sheet->setCellValue('F' . $row, $activity->user?->name ?? 'System');
            $row++;
        }

        $lastRow = max($row - 1, 2);
        // Format previous and new price columns as decimal numbers
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'price_list_activities_' . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Build the filtered query.
     */
    private function buildFilterQuery(Request $request)
    {
        $query = PriceListActivity::with(['item', 'portion', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by Date Range (From Date)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        // Filter by Date Range (To Date)
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Search by Item Name
        if ($request->filled('item_name')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('item_name') . '%');
            });
        }

        // Filter by User
        if ($request->filled('user_id')) {
            $query->where('updated_by', $request->input('user_id'));
        }

        return $query;
    }
}
