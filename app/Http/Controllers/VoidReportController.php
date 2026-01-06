<?php

namespace App\Http\Controllers;

use App\Models\VoidRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoidReportController extends Controller
{
    /**
     * Display the void report page.
     */
    public function index(Request $request)
    {
        // Default to today's date
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $supervisorId = $request->input('supervisor_id');
        $cashierId = $request->input('cashier_id');

        // Build query
        $query = VoidRecord::with(['order', 'item', 'cashier', 'supervisor'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($supervisorId) {
            $query->where('supervisor_id', $supervisorId);
        }

        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        $voidRecords = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_voids' => $voidRecords->count(),
            'total_quantity' => $voidRecords->sum('voided_quantity'),
            'total_amount' => $voidRecords->sum('voided_amount'),
            'unique_orders' => $voidRecords->unique('order_id')->count(),
        ];

        // Get supervisors and cashiers for filters
        $supervisors = User::whereHas('roles', function ($q) {
            $q->where('name', 'supervisor');
        })->get();

        $cashiers = User::whereHas('roles', function ($q) {
            $q->where('name', 'cashier');
        })->get();

        return view('reports.void-report', compact(
            'voidRecords',
            'summary',
            'supervisors',
            'cashiers',
            'startDate',
            'endDate',
            'supervisorId',
            'cashierId'
        ));
    }

    /**
     * Get void details for a specific void record (AJAX).
     */
    public function getDetails(VoidRecord $voidRecord)
    {
        $voidRecord->load(['order', 'item', 'orderItem', 'cashier', 'supervisor']);

        return response()->json([
            'void_number' => $voidRecord->void_number,
            'order_number' => $voidRecord->order_number,
            'item_name' => $voidRecord->item_name,
            'item_code' => $voidRecord->item_code,
            'voided_quantity' => $voidRecord->voided_quantity,
            'unit_price' => number_format($voidRecord->unit_price, 2),
            'voided_amount' => number_format($voidRecord->voided_amount, 2),
            'cashier_name' => $voidRecord->cashier->name ?? 'N/A',
            'supervisor_name' => $voidRecord->supervisor_name,
            'table_number' => $voidRecord->table_number ?? 'N/A',
            'cancel_kot_number' => $voidRecord->cancel_kot_number ?? 'N/A',
            'reason' => $voidRecord->reason ?? 'No reason provided',
            'created_at' => $voidRecord->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Export void report to CSV.
     */
    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $supervisorId = $request->input('supervisor_id');
        $cashierId = $request->input('cashier_id');

        $query = VoidRecord::with(['cashier', 'supervisor'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($supervisorId) {
            $query->where('supervisor_id', $supervisorId);
        }

        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        $voidRecords = $query->orderBy('created_at', 'desc')->get();

        $filename = 'void_report_' . $startDate . '_to_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($voidRecords) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Void Number',
                'Date & Time',
                'Order Number',
                'Table',
                'Item Name',
                'Item Code',
                'Quantity',
                'Unit Price',
                'Voided Amount',
                'Cashier',
                'Supervisor',
                'Cancel KOT',
                'Reason'
            ]);

            // CSV Data
            foreach ($voidRecords as $record) {
                fputcsv($file, [
                    $record->void_number,
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->order_number,
                    $record->table_number ?? 'N/A',
                    $record->item_name,
                    $record->item_code ?? 'N/A',
                    $record->voided_quantity,
                    number_format($record->unit_price, 2),
                    number_format($record->voided_amount, 2),
                    $record->cashier->name ?? 'N/A',
                    $record->supervisor_name,
                    $record->cancel_kot_number ?? 'N/A',
                    $record->reason ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
