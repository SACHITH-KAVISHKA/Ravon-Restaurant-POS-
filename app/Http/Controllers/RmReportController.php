<?php

namespace App\Http\Controllers;

use App\Models\ItemRecipe;
use App\Models\MainStockItem;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RmReportController extends Controller
{
    /**
     * Display the RM Report page.
     */
    public function index(Request $request)
    {
        $rawMaterials = MainStockItem::ofType('raw_material')->orderBy('item_name')->get();
        $selectedRmId = $request->input('raw_material_id');
        $recipes = collect();

        if ($selectedRmId) {
            $recipes = ItemRecipe::with(['item', 'modifier'])
                ->where('main_stock_item_id', $selectedRmId)
                ->get();
        }

        return view('menu.rm-report', compact('rawMaterials', 'selectedRmId', 'recipes'));
    }

    /**
     * Export the RM report to an Excel workbook.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $selectedRmId = $request->input('raw_material_id');
        $rawMaterial = null;

        $query = ItemRecipe::with(['item', 'modifier', 'mainStockItem'])
            ->orderBy('main_stock_item_id')
            ->orderBy('item_id')
            ->orderBy('item_modifier_id');

        if ($selectedRmId) {
            $rawMaterial = MainStockItem::ofType('raw_material')->findOrFail($selectedRmId);
            $query->where('main_stock_item_id', $selectedRmId);
        }

        $recipes = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RM Report');

        $headers = [
            'A1' => 'Raw Material Code',
            'B1' => 'Raw Material Name',
            'C1' => 'POS Item Name',
            'D1' => 'Portion',
            'E1' => 'Quantity Included',
            'F1' => 'Unit',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('764ba2');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $row = 2;
        foreach ($recipes as $recipe) {
            $sheet->setCellValue('A' . $row, $recipe->mainStockItem?->item_code ?? 'N/A');
            $sheet->setCellValue('B' . $row, $recipe->mainStockItem?->item_name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $recipe->item?->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $recipe->modifier?->name ?? '-');
            $sheet->setCellValue('E' . $row, (float) $recipe->quantity);
            $sheet->setCellValue('F' . $row, $recipe->mainStockItem?->unit_abbreviation ?? '');
            $row++;
        }

        $lastRow = max($row - 1, 2);
        $sheet->getStyle('E2:E' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.000');

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filenamePart = $rawMaterial
            ? preg_replace('/[^A-Za-z0-9_-]+/', '_', $rawMaterial->item_code . '_' . $rawMaterial->item_name)
            : 'all_raw_materials';
        $filename = 'rm_report_' . $filenamePart . '_' . now()->format('Ymd_His') . '.xlsx';

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
     * Update a recipe's raw material or quantity.
     */
    public function update(Request $request, ItemRecipe $recipe)
    {
        $validated = $request->validate([
            'main_stock_item_id' => 'required|exists:main_stock_items,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $recipe->update($validated);

        return redirect()->back()->with('success', 'Recipe updated successfully!');
    }

    /**
     * Remove a recipe.
     */
    public function destroy(ItemRecipe $recipe)
    {
        $recipe->delete();

        return redirect()->back()->with('success', 'Raw material removed from POS item successfully!');
    }
}
