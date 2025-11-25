<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Get daily sales report
     */
    public function dailySales(Request $request)
    {
        $this->authorize('view-reports');

        $date = $request->input('date', now()->toDateString());
        $report = $this->reportService->getDailySalesReport($date);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get item-wise sales report
     */
    public function itemSales(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getItemWiseSalesReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get category-wise sales report
     */
    public function categorySales(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getCategoryWiseSalesReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get staff performance report
     */
    public function staffPerformance(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getStaffPerformanceReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get table turnover report
     */
    public function tableTurnover(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getTableTurnoverReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get peak hours analysis
     */
    public function peakHours(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getPeakHoursAnalysis(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get monthly summary
     */
    public function monthlySummary(Request $request)
    {
        $this->authorize('view-reports');

        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $report = $this->reportService->getMonthlySummary(
            $request->year,
            $request->month
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Export report to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('export-reports');

        $request->validate([
            'report_type' => 'required|in:daily_sales,item_sales,category_sales,staff_performance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $csv = $this->reportService->exportToCSV(
            $request->report_type,
            $request->start_date,
            $request->end_date
        );

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $request->report_type . '_report.csv"');
    }
}
