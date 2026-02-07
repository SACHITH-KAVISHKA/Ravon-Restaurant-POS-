<?php

namespace App\Http\Controllers;

use App\Models\MainStockItem;
use App\Models\MainStockTransaction;
use App\Models\MainStockTransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MainStockController extends Controller
{
    /**
     * Display the main stock items listing.
     */
    public function index(Request $request)
    {
        $query = MainStockItem::with(['creator', 'updater'])
            ->orderBy('item_name');

        // Filter by type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'low_stock') {
                $query->active()->lowStock();
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(100)->withQueryString();

        // Stats
        $stats = [
            'total' => MainStockItem::count(),
            'active' => MainStockItem::active()->count(),
            'low_stock' => MainStockItem::active()->lowStock()->count(),
            'raw_materials' => MainStockItem::active()->ofType('raw_material')->count(),
            'finished_goods' => MainStockItem::active()->ofType('finished_good')->count(),
        ];

        return view('stock.supervisor.main-stock.index', compact('items', 'stats'));
    }

    /**
     * Show the form for creating a new stock item.
     */
    public function create()
    {
        $itemCode = MainStockItem::generateItemCode('other');

        // Get existing stock item names to exclude from dropdown
        $existingStockItemNames = MainStockItem::where('item_type', 'finished_good')
            ->pluck('item_name')
            ->toArray();

        // Get beverage and dessert items with their portions/sizes for finished goods dropdown
        // Items must be in Beverage/Dessert category AND marked as "Finished Goods"
        $finishedGoodsItems = \App\Models\Item::with([
            'category',
            'activeModifiers'  // Load all active modifiers (portions/sizes)
        ])
            ->whereHas('category', function ($q) {
                $q->whereIn('slug', ['beverages', 'desserts', 'beverage', 'dessert'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%beverage%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dessert%']);
            })
            ->where('is_available', true)
            ->where('is_finished_goods', true)
            ->where('is_stock_count', true)
            ->orderBy('name')
            ->get();

        // Filter out items/portions that are already added as stock items
        $finishedGoodsItems = $finishedGoodsItems->map(function ($item) use ($existingStockItemNames) {
            // Track if item originally had portions before filtering
            $originalPortionCount = $item->activeModifiers->count();
            $item->had_portions_originally = $originalPortionCount > 0;

            // Filter out portions that are already added
            if ($originalPortionCount > 0) {
                $item->setRelation('activeModifiers', $item->activeModifiers->filter(function ($portion) use ($item, $existingStockItemNames) {
                    $portionName = $item->name . ' - ' . $portion->name;
                    return !in_array($portionName, $existingStockItemNames);
                }));
            }
            return $item;
        })->filter(function ($item) use ($existingStockItemNames) {
            // If item originally had portions, keep it ONLY if at least one portion remains
            if ($item->had_portions_originally) {
                return $item->activeModifiers->count() > 0;
            }
            // If item never had portions, check if the item itself is already added
            return !in_array($item->name, $existingStockItemNames);
        });

        return view('stock.supervisor.main-stock.create', compact('itemCode', 'finishedGoodsItems'));
    }

    /**
     * Store newly created stock items (supports bulk creation).
     */
    public function store(Request $request)
    {
        // Validate items array
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string|max:50',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.unit_type' => ['required', Rule::in(array_keys(MainStockItem::UNIT_TYPES))],
            'items.*.item_type' => ['required', Rule::in(array_keys(MainStockItem::ITEM_TYPES))],
            'items.*.linked_item_id' => 'nullable|exists:items,id',
            'items.*.linked_item_modifier_id' => 'nullable|exists:item_modifiers,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.normalization' => 'nullable|numeric|min:0',
        ]);

        // Check for duplicate item codes and names within the submitted items
        $itemCodes = [];
        $itemNames = [];
        $errors = [];

        foreach ($validated['items'] as $index => $itemData) {
            // Check for duplicates within submitted items
            if (in_array($itemData['item_code'], $itemCodes)) {
                $errors["items.{$index}.item_code"] = "Duplicate item code in the list";
            }
            if (in_array(strtolower($itemData['item_name']), $itemNames)) {
                $errors["items.{$index}.item_name"] = "Duplicate item name in the list";
            }
            $itemCodes[] = $itemData['item_code'];
            $itemNames[] = strtolower($itemData['item_name']);

            // Check for existing items in database
            if (MainStockItem::where('item_code', $itemData['item_code'])->exists()) {
                $errors["items.{$index}.item_code"] = "Item code '{$itemData['item_code']}' already exists";
            }
            if (MainStockItem::where('item_name', $itemData['item_name'])->exists()) {
                $errors["items.{$index}.item_name"] = "Item Already Exists: '{$itemData['item_name']}'";
            }

            // Check for duplicate linked item + modifier combination (for finished goods)
            if (!empty($itemData['linked_item_id'])) {
                $existingLink = MainStockItem::where('linked_item_id', $itemData['linked_item_id'])
                    ->where('linked_item_modifier_id', $itemData['linked_item_modifier_id'] ?? null)
                    ->exists();
                if ($existingLink) {
                    $errors["items.{$index}.linked_item_id"] = "This menu item/portion is already linked to a stock item";
                }
            }
        }

        if (!empty($errors)) {
            return back()
                ->withInput()
                ->withErrors($errors);
        }

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($validated['items'] as $itemData) {
                $quantity = $itemData['quantity'] ?? 0;

                // Parse linked_item_id which may contain "itemId_modifierId" format
                $linkedItemId = null;
                $linkedModifierId = null;

                if (!empty($itemData['linked_item_id'])) {
                    $linkedValue = $itemData['linked_item_id'];
                    if (str_contains($linkedValue, '_')) {
                        // Format: itemId_modifierId
                        [$linkedItemId, $linkedModifierId] = explode('_', $linkedValue);
                    } else {
                        // Just itemId
                        $linkedItemId = $linkedValue;
                    }
                }

                // Normalization only applies to raw materials
                $normalization = ($itemData['item_type'] === 'raw_material' && !empty($itemData['normalization']))
                    ? $itemData['normalization']
                    : null;

                $item = MainStockItem::create([
                    'item_code' => $itemData['item_code'],
                    'item_name' => $itemData['item_name'],
                    'unit_type' => $itemData['unit_type'],
                    'item_type' => $itemData['item_type'],
                    'linked_item_id' => $linkedItemId,
                    'linked_item_modifier_id' => $linkedModifierId,
                    'quantity' => $quantity,
                    'normalization' => $normalization,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // Create initial stock transaction if quantity > 0
                if ($quantity > 0) {
                    MainStockTransaction::create([
                        'main_stock_item_id' => $item->id,
                        'transaction_type' => 'stock_in',
                        'quantity' => $quantity,
                        'quantity_before' => 0,
                        'quantity_after' => $quantity,
                        'notes' => 'Initial stock entry',
                        'performed_by' => Auth::id(),
                    ]);
                }

                $createdCount++;
            }

            DB::commit();

            $message = $createdCount === 1
                ? 'Stock item created successfully!'
                : "{$createdCount} stock items created successfully!";

            return redirect()
                ->route('main-stock.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create stock items: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a stock item.
     */
    public function edit(MainStockItem $mainStock)
    {
        // Get existing stock item names to exclude from dropdown (except current item)
        $existingStockItemNames = MainStockItem::where('item_type', 'finished_good')
            ->where('id', '!=', $mainStock->id)
            ->pluck('item_name')
            ->toArray();

        // Get beverage and dessert items with their portions/sizes for finished goods dropdown
        // Items must be in Beverage/Dessert category AND marked as "Finished Goods"
        $finishedGoodsItems = \App\Models\Item::with([
            'category',
            'activeModifiers'  // Load all active modifiers (portions/sizes)
        ])
            ->whereHas('category', function ($q) {
                $q->whereIn('slug', ['beverages', 'desserts', 'beverage', 'dessert'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%beverage%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dessert%']);
            })
            ->where('is_available', true)
            ->where('is_finished_goods', true)
            ->where('is_stock_count', true)
            ->orderBy('name')
            ->get();

        // Filter out items/portions that are already added as stock items
        $finishedGoodsItems = $finishedGoodsItems->map(function ($item) use ($existingStockItemNames) {
            // Track if item originally had portions before filtering
            $originalPortionCount = $item->activeModifiers->count();
            $item->had_portions_originally = $originalPortionCount > 0;

            // Filter out portions that are already added
            if ($originalPortionCount > 0) {
                $item->setRelation('activeModifiers', $item->activeModifiers->filter(function ($portion) use ($item, $existingStockItemNames) {
                    $portionName = $item->name . ' - ' . $portion->name;
                    return !in_array($portionName, $existingStockItemNames);
                }));
            }
            return $item;
        })->filter(function ($item) use ($existingStockItemNames) {
            // If item originally had portions, keep it ONLY if at least one portion remains
            if ($item->had_portions_originally) {
                return $item->activeModifiers->count() > 0;
            }
            // If item never had portions, check if the item itself is already added
            return !in_array($item->name, $existingStockItemNames);
        });

        return view('stock.supervisor.main-stock.edit', [
            'item' => $mainStock,
            'finishedGoodsItems' => $finishedGoodsItems
        ]);
    }

    /**
     * Update the specified stock item.
     */
    public function update(Request $request, MainStockItem $mainStock)
    {
        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', Rule::unique('main_stock_items', 'item_code')->ignore($mainStock->id)],
            'item_name' => ['required', 'string', 'max:255', Rule::unique('main_stock_items', 'item_name')->ignore($mainStock->id)],
            'unit_type' => ['required', Rule::in(array_keys(MainStockItem::UNIT_TYPES))],
            'item_type' => ['required', Rule::in(array_keys(MainStockItem::ITEM_TYPES))],
            'normalization' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'item_name.unique' => 'Item Already Exists',
        ]);

        // Normalization only applies to raw materials
        $normalization = ($validated['item_type'] === 'raw_material' && !empty($validated['normalization']))
            ? $validated['normalization']
            : null;

        $mainStock->update([
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'unit_type' => $validated['unit_type'],
            'item_type' => $validated['item_type'],
            'normalization' => $normalization,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('main-stock.index')
            ->with('success', 'Stock item updated successfully!');
    }

    /**
     * Delete the specified stock item.
     */
    public function destroy(MainStockItem $mainStock)
    {
        $mainStock->delete();
        return redirect()
            ->route('main-stock.index')
            ->with('success', 'Stock item deleted successfully!');
    }

    /**
     * Show stock update page for adding/removing stock.
     */
    public function showStockUpdate()
    {
        $items = MainStockItem::active()
            ->orderBy('item_name')
            ->get();

        return view('stock.supervisor.main-stock.stock-update', compact('items'));
    }

    /**
     * Process stock update (add or remove).
     * Handles bulk items - creates one transaction with multiple items.
     */
    public function processStockUpdate(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:main_stock_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Create a single transaction for all items
            $transaction = MainStockTransaction::create([
                'transaction_type' => 'stock_in',
                'quantity' => 0, // Will be sum of all items
                'quantity_before' => 0,
                'quantity_after' => 0,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? 'Bulk stock update',
                'performed_by' => Auth::id(),
            ]);

            $totalQuantity = 0;
            $updatedItems = [];

            foreach ($validated['items'] as $itemData) {
                $item = MainStockItem::findOrFail($itemData['item_id']);
                $inputQty = $itemData['quantity'];

                // Apply normalization if item is raw material and has normalization value
                $qty = $inputQty;
                if ($item->item_type === 'raw_material' && $item->normalization) {
                    $qty = $inputQty * (float) $item->normalization;
                }

                $quantityBefore = $item->quantity;

                // Add to main stock
                $item->quantity += $qty;
                $item->updated_by = Auth::id();
                $item->save();

                $quantityAfter = $item->quantity;

                // Create transaction item record
                MainStockTransactionItem::create([
                    'main_stock_transaction_id' => $transaction->id,
                    'main_stock_item_id' => $item->id,
                    'quantity' => $qty,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                ]);

                $totalQuantity += $qty;
                $updatedItems[] = [
                    'item_name' => $item->item_name,
                    'quantity_added' => $qty,
                    'input_quantity' => $inputQty,
                    'normalization' => $item->normalization,
                    'new_quantity' => $quantityAfter,
                    'unit' => $item->unit_abbreviation,
                ];
            }

            // Update transaction totals
            $transaction->update([
                'quantity' => $totalQuantity,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully! ' . count($updatedItems) . ' item(s) updated.',
                'transaction_number' => $transaction->transaction_number,
                'updated_items' => $updatedItems,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get item details via AJAX.
     */
    public function getItem(MainStockItem $mainStock)
    {
        return response()->json([
            'id' => $mainStock->id,
            'item_code' => $mainStock->item_code,
            'item_name' => $mainStock->item_name,
            'unit_type' => $mainStock->unit_type,
            'unit_abbreviation' => $mainStock->unit_abbreviation,
            'item_type' => $mainStock->item_type,
            'item_type_label' => $mainStock->item_type_label,
            'quantity' => $mainStock->quantity,
            'min_quantity' => $mainStock->min_quantity,
            'is_low_stock' => $mainStock->isLowStock(),
        ]);
    }

    /**
     * Generate a new item code via AJAX.
     */
    public function generateCode(Request $request)
    {
        $type = $request->get('type', 'other');
        $offset = (int) $request->get('offset', 0);
        $code = MainStockItem::generateItemCode($type, $offset);
        return response()->json(['code' => $code]);
    }
}
