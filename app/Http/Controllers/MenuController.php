<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemModifier;
use App\Models\ItemPrice;
use App\Models\ItemRecipe;
use App\Models\MainStockItem;
use App\Models\KitchenStation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuController extends Controller
{
    /**
     * Display menu management page.
     */
    public function index()
    {
        $categories = Category::with(['items.modifiers'])->get();
        $items = Item::with(['category', 'modifiers'])->orderBy('display_order')->get();
        
        $inactiveItems = Item::withoutGlobalScope('active')
            ->where('status', 0)
            ->with(['category'])
            ->get();

        $inactivePortions = ItemModifier::withoutGlobalScope('active')
            ->where('status', 0)
            ->whereHas('item', function ($query) {
                $query->where('status', 1);
            })
            ->with(['item.category', 'item.modifiers' => function ($query) {
                $query->withoutGlobalScope('active');
            }])
            ->get();

        return view('menu.index', compact('categories', 'items', 'inactiveItems', 'inactivePortions'));
    }

    /**
     * Display category management page.
     */
    public function indexCategories()
    {
        $categories = Category::withCount('items')->orderBy('display_order')->get();
        $nextDisplayOrder = (Category::max('display_order') ?? 0) + 1;
        return view('menu.categories.index', compact('categories', 'nextDisplayOrder'));
    }

    /**
     * Show form to create a new category.
     */
    public function createCategory()
    {
        return view('menu.categories.create');
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Handle display order collision - shift existing categories up if needed
        if (isset($validated['display_order'])) {
            $displayOrder = $validated['display_order'];

            // Check if this display order is already used
            $existingCategory = Category::where('display_order', $displayOrder)->first();

            if ($existingCategory) {
                // Shift all categories with display_order >= the requested order up by 1
                Category::where('display_order', '>=', $displayOrder)
                    ->orderBy('display_order', 'desc')
                    ->each(function ($cat) {
                        $cat->display_order = $cat->display_order + 1;
                        $cat->save();
                    });
            }
        }

        Category::create($validated);

        return redirect()->route('menu.categories.index')->with('success', 'Category created successfully!');
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Handle display order collision - shift existing categories if needed
        if (isset($validated['display_order'])) {
            $newDisplayOrder = $validated['display_order'];
            $oldDisplayOrder = $category->display_order;

            // Only handle collision if the display order actually changed
            if ($newDisplayOrder != $oldDisplayOrder) {
                // Check if this display order is already used by another category
                $existingCategory = Category::where('display_order', $newDisplayOrder)
                    ->where('id', '!=', $category->id)
                    ->first();

                if ($existingCategory) {
                    // Shift all categories with display_order >= the new order (except current) up by 1
                    Category::where('display_order', '>=', $newDisplayOrder)
                        ->where('id', '!=', $category->id)
                        ->orderBy('display_order', 'desc')
                        ->each(function ($cat) {
                            $cat->display_order = $cat->display_order + 1;
                            $cat->save();
                        });
                }
            }
        }

        $category->update($validated);

        return redirect()->route('menu.categories.index')->with('success', 'Category updated successfully!');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(Category $category)
    {
        if ($category->items()->count() > 0) {
            return redirect()->route('menu.categories.index')->with('error', 'Cannot delete category with existing items!');
        }

        $deletedDisplayOrder = $category->display_order;
        $category->delete();

        // Shift all categories with display_order > deleted order down by 1 to fill the gap
        if ($deletedDisplayOrder) {
            Category::where('display_order', '>', $deletedDisplayOrder)
                ->orderBy('display_order', 'asc')
                ->each(function ($cat) {
                    $cat->display_order = $cat->display_order - 1;
                    $cat->save();
                });
        }

        return redirect()->route('menu.categories.index')->with('success', 'Category deleted successfully!');
    }

    /**
     * Show form to create a new item.
     */
    public function createItem()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $kitchenStations = KitchenStation::all();
        $rawMaterials = MainStockItem::active()
            ->ofType('raw_material')
            ->orderBy('item_name')
            ->get();

        return view('menu.items.create', compact('categories', 'kitchenStations', 'rawMaterials'));
    }

    /**
     * Store a new item with portions.
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'special_prices' => 'nullable|array',
            'has_portions' => 'nullable|boolean',
            'is_finished_goods' => 'nullable|boolean',
            'is_stock_count' => 'nullable|boolean',
            'portions' => 'nullable|array',
            'portions.*.name' => 'required_with:portions|string|max:255',
            'portions.*.price' => 'required_with:portions|numeric|min:0',
            'portions.*.special_prices' => 'nullable|array',
        ]);

        // Set defaults
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_available'] = true;
        $validated['is_featured'] = false;
        $validated['is_finished_goods'] = $request->has('is_finished_goods');
        $validated['is_stock_count'] = $request->has('is_stock_count');
        $validated['vat_available'] = $request->has('vat_available');
        $validated['sscl_available'] = $request->has('sscl_available');
        $validated['pork_available'] = $request->has('pork_available');
        $validated['display_order'] = 0;
        $validated['price'] = $validated['price'] ?? 0;

        // Create the item
        $item = Item::create($validated);

        // Store special prices for the item
        if ($request->has('special_prices.new')) {
            foreach ($request->input('special_prices.new') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => null,
                ]);
            }
        }

        // Store recipes for the item (only when no portions)
        if ($request->has('recipes') && is_array($request->recipes)) {
            foreach ($request->recipes as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => null,
                    ]);
                }
            }
        }

        // If has portions, create modifiers with independent prices
        if ($request->has('portions') && is_array($request->portions)) {
            foreach ($request->portions as $portionIndex => $portion) {
                if (!empty($portion['name']) && isset($portion['price'])) {
                    $modifier = $item->modifiers()->create([
                        'name' => $portion['name'],
                        'type' => 'size',
                        'price_adjustment' => $portion['price'], // Store as independent price
                        'is_active' => true,
                        'pork_available' => isset($portion['pork_available']),
                    ]);

                    // Store special prices for this portion if provided
                    if (isset($portion['special_prices']) && is_array($portion['special_prices'])) {
                        foreach ($portion['special_prices'] as $priceData) {
                            $item->itemPrices()->create([
                                'price_type' => $priceData['type'],
                                'price' => $priceData['price'],
                                'item_modifier_id' => $modifier->id,
                            ]);
                        }
                    }

                    // Store recipes for this portion if provided
                    if (isset($portion['recipes']) && is_array($portion['recipes'])) {
                        foreach ($portion['recipes'] as $recipeData) {
                            if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                                $item->recipes()->create([
                                    'main_stock_item_id' => $recipeData['main_stock_item_id'],
                                    'quantity' => $recipeData['quantity'],
                                    'item_modifier_id' => $modifier->id,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return redirect()->route('menu.index')->with('success', 'Item created successfully!');
    }

    /**
     * Show form to edit an item.
     */
    public function editItem(Item $item)
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $kitchenStations = KitchenStation::all();
        $rawMaterials = MainStockItem::active()
            ->ofType('raw_material')
            ->orderBy('item_name')
            ->get();
        $item->load(['modifiers.recipes.mainStockItem', 'itemRecipes.mainStockItem']);

        return view('menu.items.edit', compact('item', 'categories', 'kitchenStations', 'rawMaterials'));
    }

    /**
     * Update an item.
     */
    public function updateItem(Request $request, Item $item)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'special_prices' => 'nullable|array',
            'is_finished_goods' => 'nullable|boolean',
            'is_stock_count' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['price'] = $validated['price'] ?? $item->price;
        $validated['is_finished_goods'] = $request->has('is_finished_goods');
        $validated['is_stock_count'] = $request->has('is_stock_count');
        $validated['vat_available'] = $request->has('vat_available');
        $validated['sscl_available'] = $request->has('sscl_available');
        $validated['pork_available'] = $request->has('pork_available');

        $item->update($validated);

        // Handle special prices deletion
        if ($request->has('special_prices.delete')) {
            ItemPrice::whereIn('id', $request->input('special_prices.delete'))->delete();
        }

        // Handle existing special prices updates
        if ($request->has('special_prices.existing')) {
            foreach ($request->input('special_prices.existing') as $priceData) {
                if (isset($priceData['id'])) {
                    $itemPrice = ItemPrice::find($priceData['id']);
                    if ($itemPrice && $itemPrice->item_id === $item->id) {
                        $itemPrice->update([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                        ]);
                    }
                }
            }
        }

        // Handle new special prices
        if ($request->has('special_prices.new')) {
            foreach ($request->input('special_prices.new') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => null,
                ]);
            }
        }

        // Handle recipe deletion
        if ($request->has('recipes.delete')) {
            ItemRecipe::whereIn('id', $request->input('recipes.delete'))->delete();
        }

        // Handle existing recipe updates
        if ($request->has('recipes.existing')) {
            foreach ($request->input('recipes.existing') as $recipeData) {
                if (isset($recipeData['id'])) {
                    $recipe = ItemRecipe::find($recipeData['id']);
                    if ($recipe && $recipe->item_id === $item->id) {
                        $recipe->update([
                            'main_stock_item_id' => $recipeData['main_stock_item_id'],
                            'quantity' => $recipeData['quantity'],
                        ]);
                    }
                }
            }
        }

        // Handle new recipes
        if ($request->has('recipes.new')) {
            foreach ($request->input('recipes.new') as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => null,
                    ]);
                }
            }
        }

        return redirect()->route('menu.items.edit', $item)->with('success', 'Item updated successfully!');
    }

    /**
     * Delete an item.
     */
    public function destroyItem(Item $item)
    {
        $item->status = 0;
        $item->save();

        return redirect()->route('menu.index')->with('success', 'Item deleted successfully!');
    }

    /**
     * Store a new modifier for an item.
     */
    public function storeModifier(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'portion_special_prices' => 'nullable|array',
        ]);

        $modifier = $item->modifiers()->create([
            'name' => $validated['name'],
            'type' => 'size',
            'price_adjustment' => $validated['price'],
            'is_active' => true,
            'pork_available' => $request->has('pork_available'),
        ]);

        // Store special prices for this portion
        if ($request->has('portion_special_prices')) {
            foreach ($request->input('portion_special_prices') as $priceData) {
                $item->itemPrices()->create([
                    'price_type' => $priceData['type'],
                    'price' => $priceData['price'],
                    'item_modifier_id' => $modifier->id,
                ]);
            }
        }

        // Store recipes for this portion
        if ($request->has('portion_recipes')) {
            foreach ($request->input('portion_recipes') as $recipeData) {
                if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                    $item->recipes()->create([
                        'main_stock_item_id' => $recipeData['main_stock_item_id'],
                        'quantity' => $recipeData['quantity'],
                        'item_modifier_id' => $modifier->id,
                    ]);
                }
            }
        }

        return redirect()->route('menu.items.edit', $item)->with('success', 'Portion added successfully!');
    }

    /**
     * Update a modifier.
     */
    public function updateModifier(Request $request, ItemModifier $modifier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'modifier_special_prices' => 'nullable|array',
        ]);

        $modifier->update([
            'name' => $validated['name'],
            'price_adjustment' => $validated['price'],
            'pork_available' => $request->has('pork_available'),
        ]);

        // Handle special prices deletion
        if ($request->has('modifier_special_prices.delete')) {
            ItemPrice::whereIn('id', $request->input('modifier_special_prices.delete'))->delete();
        }

        // Handle existing special prices updates
        if ($request->has('modifier_special_prices.existing')) {
            foreach ($request->input('modifier_special_prices.existing') as $priceData) {
                if (isset($priceData['id'])) {
                    $itemPrice = ItemPrice::find($priceData['id']);
                    if ($itemPrice && $itemPrice->item_modifier_id === $modifier->id) {
                        $itemPrice->update([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                        ]);
                    }
                }
            }
        }

        // Handle new special prices
        if ($request->has('modifier_special_prices.new')) {
            foreach ($request->input('modifier_special_prices.new') as $modifierId => $prices) {
                if ($modifierId == $modifier->id) {
                    foreach ($prices as $priceData) {
                        $modifier->item->itemPrices()->create([
                            'price_type' => $priceData['type'],
                            'price' => $priceData['price'],
                            'item_modifier_id' => $modifier->id,
                        ]);
                    }
                }
            }
        }

        // Handle recipe deletion for this modifier
        if ($request->has('modifier_recipes.delete')) {
            ItemRecipe::whereIn('id', $request->input('modifier_recipes.delete'))->delete();
        }

        // Handle existing recipe updates for this modifier
        if ($request->has('modifier_recipes.existing')) {
            foreach ($request->input('modifier_recipes.existing') as $recipeData) {
                if (isset($recipeData['id'])) {
                    $recipe = ItemRecipe::find($recipeData['id']);
                    if ($recipe && $recipe->item_modifier_id === $modifier->id) {
                        $recipe->update([
                            'main_stock_item_id' => $recipeData['main_stock_item_id'],
                            'quantity' => $recipeData['quantity'],
                        ]);
                    }
                }
            }
        }

        // Handle new recipes for this modifier
        if ($request->has('modifier_recipes.new')) {
            foreach ($request->input('modifier_recipes.new') as $modId => $recipes) {
                if ($modId == $modifier->id) {
                    foreach ($recipes as $recipeData) {
                        if (!empty($recipeData['main_stock_item_id']) && isset($recipeData['quantity']) && $recipeData['quantity'] > 0) {
                            $modifier->item->recipes()->create([
                                'main_stock_item_id' => $recipeData['main_stock_item_id'],
                                'quantity' => $recipeData['quantity'],
                                'item_modifier_id' => $modifier->id,
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->route('menu.items.edit', $modifier->item)->with('success', 'Portion updated successfully!');
    }

    /**
     * Delete a modifier.
     */
    public function destroyModifier(ItemModifier $modifier)
    {
        $item = $modifier->item;
        $modifier->status = 0;
        $modifier->save();

        return redirect()->route('menu.items.edit', $item)->with('success', 'Portion deleted successfully!');
    }

    /**
     * Activate a menu item.
     */
    public function activateItem($id)
    {
        $item = Item::withoutGlobalScope('active')->findOrFail($id);
        $item->status = 1;
        $item->save();

        return redirect()->route('menu.index')->with('success', 'Item activated successfully!');
    }

    /**
     * Activate a portion/modifier.
     */
    public function activateModifier($id)
    {
        $modifier = ItemModifier::withoutGlobalScope('active')->findOrFail($id);
        $modifier->status = 1;
        $modifier->save();

        return redirect()->route('menu.index')->with('success', 'Portion activated successfully!');
    }

    /**
     * Get inactive items (JSON).
     */
    public function inactiveItems()
    {
        $items = Item::withoutGlobalScope('active')
            ->where('status', 0)
            ->with(['category'])
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    /**
     * Get inactive portions (JSON).
     */
    public function inactivePortions()
    {
        $portions = ItemModifier::withoutGlobalScope('active')
            ->where('status', 0)
            ->with(['item.category'])
            ->get();

        return response()->json([
            'success' => true,
            'portions' => $portions
        ]);
    }

    /**
     * Export active menu items and portions to a professionally formatted Excel price list.
     */
    public function exportPriceList()
    {
        $categories = Category::with(['items' => function($q) {
            $q->where('status', 1)->orderBy('name');
        }, 'items.modifiers' => function($q) {
            $q->where('status', 1)->orderBy('name');
        }])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('POS Price List');

        // Set Headers
        $sheet->setCellValue('A1', 'Category');
        $sheet->setCellValue('B1', 'Item Name');
        $sheet->setCellValue('C1', 'Portion');
        $sheet->setCellValue('D1', 'Price');

        // Freeze the header row
        $sheet->freezePane('A2');

        // Header Styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF667EEA'], // Purple color matching UI
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 2;

        foreach ($categories as $category) {
            foreach ($category->items as $item) {
                $hasPortions = $item->modifiers->isNotEmpty();

                if (!$hasPortions) {
                    $sheet->setCellValue('A' . $row, $category->name);
                    $sheet->setCellValue('B' . $row, $item->name);
                    $sheet->setCellValue('C' . $row, '-');
                    $sheet->setCellValue('D' . $row, $item->price);
                    
                    // Zebra striping
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
                    }
                    // Price formatting & alignment
                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"Rs. " #,##0');
                    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $row++;
                } else {
                    // Item row as a header/parent
                    $sheet->setCellValue('A' . $row, $category->name);
                    $sheet->setCellValue('B' . $row, $item->name);
                    $sheet->setCellValue('C' . $row, '');
                    $sheet->setCellValue('D' . $row, '');
                    
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
                    }
                    $row++;

                    // Portion rows
                    foreach ($item->modifiers as $portion) {
                        $sheet->setCellValue('A' . $row, '');
                        $sheet->setCellValue('B' . $row, '  ↳ ' . $portion->name);
                        $sheet->setCellValue('C' . $row, $portion->name);
                        $sheet->setCellValue('D' . $row, $portion->price_adjustment);

                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
                        }
                        
                        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"Rs. " #,##0');
                        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $row++;
                    }
                }
            }
        }

        $lastRow = $row - 1;

        // Apply borders to all cells
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE5E7EB'], // Light gray borders
                ],
            ],
        ];
        if ($lastRow >= 1) {
            $sheet->getStyle('A1:D' . $lastRow)->applyFromArray($borderStyle);
        }

        // Auto-fit column widths
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Alignments
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $filename = 'POS_Price_List_' . date('Y-m-d') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
