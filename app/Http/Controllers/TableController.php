<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Order;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::with(['floor', 'currentOrder'])
            ->orderBy('floor_id')
            ->orderBy('table_number')
            ->get();
        
        return view('tables.index', compact('tables'));
    }

    public function show($id)
    {
        $table = Table::with(['floor', 'currentOrder.items.item', 'currentOrder.items.modifiers'])
            ->findOrFail($id);
        
        $categories = Category::with(['items' => function($query) {
            $query->where('is_available', true);
        }])->get();
        
        return view('tables.show', compact('table', 'categories'));
    }
}
