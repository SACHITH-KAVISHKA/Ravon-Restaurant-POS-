<?php

namespace App\Http\Controllers\Admin\Analysis;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CostReportController extends Controller
{
    /**
     * Display the Cost Report page.
     *
     * Loads all POS items with portions, calculates recipe cost dynamically
     * from item_recipes → main_stock_items, and displays profit analysis.
     */
    public function index(Request $request)
    {
        $itemId    = $request->input('item_id');
        $hasRecipe = $request->input('has_recipe', 'all');
        $sortBy    = $request->input('sort_by', 'alpha');

        $rows = $this->buildReportData($itemId, $hasRecipe, $sortBy);

        // Summary stats (computed from all filtered data, before pagination)
        $summary = (object) [
            'total'          => $rows->count(),
            'with_recipe'    => $rows->where('has_recipe', true)->count(),
            'without_recipe' => $rows->where('has_recipe', false)->count(),
            'avg_profit_pct' => $rows->count() > 0 ? round($rows->avg('profit_pct'), 2) : 0,
        ];

        // Paginate
        $page    = (int) $request->input('page', 1);
        $perPage = 50;

        $paginatedRows = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // All items for filter dropdown (alphabetical)
        $allItems = Item::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.analysis.cost-report', compact(
            'paginatedRows',
            'summary',
            'allItems',
            'itemId',
            'hasRecipe',
            'sortBy'
        ));
    }

    /**
     * Export the filtered Cost Report as an Excel file.
     * Includes ALL filtered records (not just current page).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $itemId    = $request->input('item_id');
        $hasRecipe = $request->input('has_recipe', 'all');
        $sortBy    = $request->input('sort_by', 'alpha');

        $rows = $this->buildReportData($itemId, $hasRecipe, $sortBy);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cost Report');

        // Title row
        $sheet->setCellValue('A1', 'RAVON RESTAURANT - Cost Report');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Filter info row
        $filterParts = [];
        if ($itemId) {
            $itemName      = Item::find($itemId)?->name ?? 'Unknown';
            $filterParts[] = "Item: $itemName";
        } else {
            $filterParts[] = 'Item: All';
        }
        if ($hasRecipe !== 'all') {
            $filterParts[] = 'Recipe: ' . ucfirst($hasRecipe);
        }
        $filterParts[] = 'Sort: ' . match ($sortBy) {
            'profit_high' => 'Profit % (Highest → Lowest)',
            'profit_low'  => 'Profit % (Lowest → Highest)',
            default       => 'Alphabetical (A-Z)',
        };
        $sheet->setCellValue('A2', implode(' | ', $filterParts));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row
        $headerRow = 4;
        $headers   = [
            'A' => 'Menu Item',
            'B' => 'Portion',
            'C' => 'Selling Price',
            'D' => 'Recipe Cost',
            'E' => 'Profit',
            'F' => 'Profit %',
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
        foreach ($rows as $item) {
            $sheet->setCellValue('A' . $row, $item->menu_item);
            $sheet->setCellValue('B' . $row, $item->portion);
            $sheet->setCellValue('C' . $row, $item->selling_price);
            $sheet->setCellValue('D' . $row, $item->recipe_cost);
            $sheet->setCellValue('E' . $row, $item->profit);
            $sheet->setCellValue('F' . $row, number_format($item->profit_pct, 2) . '%');

            // Zebra row styling
            if (($row - $headerRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }

            $row++;
        }

        // Right-align numeric columns (C-F)
        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            foreach (['C', 'D', 'E', 'F'] as $col) {
                $sheet->getStyle("{$col}" . ($headerRow + 1) . ":{$col}{$lastDataRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            // Format currency columns to 2 decimal places
            foreach (['C', 'D', 'E'] as $col) {
                $sheet->getStyle("{$col}" . ($headerRow + 1) . ":{$col}{$lastDataRow}")
                    ->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'cost_report_' . date('Y-m-d_His') . '.xlsx';

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
     * Build the report data collection.
     *
     * Logic:
     *   - Items WITHOUT modifiers → 1 row (portion = "-")
     *   - Items WITH modifiers    → 1 row per modifier/portion
     *   - Recipe cost is calculated dynamically from item_recipes → main_stock_items
     *     using: SUM(recipe.quantity × (stock_item.price / stock_item.normalization))
     */
    private function buildReportData($itemId, string $hasRecipe, string $sortBy)
    {
        // Eager load items with modifiers and recipes (avoids N+1)
        $query = Item::with([
            'modifiers',
            'recipes.mainStockItem',
        ])->orderBy('name');

        // Filter by specific item
        if ($itemId) {
            $query->where('id', $itemId);
        }

        $items = $query->get();

        $rows = collect();

        foreach ($items as $item) {
            $allRecipes = $item->recipes;

            if ($item->modifiers->isEmpty()) {
                // Item has NO portions → single row
                $itemLevelRecipes = $allRecipes->whereNull('item_modifier_id');
                $recipeCost       = $this->calculateRecipeCost($itemLevelRecipes);
                $hasRecipeFlag    = $itemLevelRecipes->isNotEmpty();
                $sellingPrice     = (float) $item->price;
                $profit           = $sellingPrice - $recipeCost;
                $profitPct        = $sellingPrice > 0 ? round(($profit / $sellingPrice) * 100, 2) : 0;

                $rows->push((object) [
                    'menu_item'     => $item->name,
                    'portion'       => '-',
                    'selling_price' => $sellingPrice,
                    'recipe_cost'   => $recipeCost,
                    'profit'        => $profit,
                    'profit_pct'    => $profitPct,
                    'has_recipe'    => $hasRecipeFlag,
                ]);
            } else {
                // Item has portions → one row per modifier
                foreach ($item->modifiers as $modifier) {
                    // Try portion-specific recipes first
                    $portionRecipes = $allRecipes->where('item_modifier_id', $modifier->id);

                    // Fallback to item-level recipes if no portion-specific recipes exist
                    if ($portionRecipes->isEmpty()) {
                        $portionRecipes = $allRecipes->whereNull('item_modifier_id');
                    }

                    $recipeCost    = $this->calculateRecipeCost($portionRecipes);
                    $hasRecipeFlag = $portionRecipes->isNotEmpty();
                    $sellingPrice  = (float) $modifier->price_adjustment;
                    $profit        = $sellingPrice - $recipeCost;
                    $profitPct     = $sellingPrice > 0 ? round(($profit / $sellingPrice) * 100, 2) : 0;

                    $rows->push((object) [
                        'menu_item'     => $item->name,
                        'portion'       => $modifier->name,
                        'selling_price' => $sellingPrice,
                        'recipe_cost'   => $recipeCost,
                        'profit'        => $profit,
                        'profit_pct'    => $profitPct,
                        'has_recipe'    => $hasRecipeFlag,
                    ]);
                }
            }
        }

        // Apply has_recipe filter
        if ($hasRecipe === 'yes') {
            $rows = $rows->filter(fn($r) => $r->has_recipe);
        } elseif ($hasRecipe === 'no') {
            $rows = $rows->filter(fn($r) => !$r->has_recipe);
        }

        // Apply sorting
        $rows = match ($sortBy) {
            'profit_high' => $rows->sortByDesc('profit_pct')->values(),
            'profit_low'  => $rows->sortBy('profit_pct')->values(),
            default       => $rows->sortBy('menu_item', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };

        return $rows;
    }

    /**
     * Calculate recipe cost from a collection of ItemRecipe records.
     *
     * Formula per recipe row:
     *   unit_cost = main_stock_item.price / main_stock_item.normalization
     *   row_cost  = unit_cost × recipe.quantity
     *
     * Total = SUM(all row costs)
     */
    private function calculateRecipeCost($recipes): float
    {
        $totalCost = 0;

        foreach ($recipes as $recipe) {
            // Soft-deleted stock items contribute no cost
            if ($recipe->mainStockItem && !$recipe->mainStockItem->isDeleted()) {
                $price         = (float) ($recipe->mainStockItem->price ?? 0);
                $normalization = (float) ($recipe->mainStockItem->normalization ?? 0);
                $unitCost      = $normalization > 0 ? ($price / $normalization) : 0;
                $totalCost    += $unitCost * (float) $recipe->quantity;
            }
        }

        return round($totalCost, 2);
    }
}
