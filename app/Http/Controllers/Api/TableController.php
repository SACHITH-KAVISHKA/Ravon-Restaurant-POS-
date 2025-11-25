<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TableService;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function __construct(
        private TableService $tableService
    ) {}

    /**
     * Get all floors with tables
     */
    public function index()
    {
        $floors = $this->tableService->getFloorsWithTables();

        return response()->json([
            'success' => true,
            'data' => $floors,
        ]);
    }

    /**
     * Get table details
     */
    public function show(Table $table)
    {
        $table->load(['floor', 'currentOrder.items.item', 'currentOrder.waiter']);

        return response()->json([
            'success' => true,
            'data' => $table,
        ]);
    }

    /**
     * Update table status
     */
    public function updateStatus(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|in:available,ordered,serving,bill_requested',
        ]);

        $table->update(['status' => $request->status]);

        // Broadcast table status update
        broadcast(new \App\Events\TableStatusUpdated($table))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Table status updated',
            'data' => $table,
        ]);
    }

    /**
     * Merge tables
     */
    public function merge(Request $request)
    {
        $request->validate([
            'source_table_ids' => 'required|array|min:2',
            'source_table_ids.*' => 'required|exists:tables,id',
            'target_table_id' => 'required|exists:tables,id',
        ]);

        $result = $this->tableService->mergeTables(
            $request->source_table_ids,
            $request->target_table_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Tables merged successfully',
            'data' => $result,
        ]);
    }

    /**
     * Split table
     */
    public function split(Request $request, Table $table)
    {
        $request->validate([
            'new_table_id' => 'required|exists:tables,id',
            'order_item_ids' => 'required|array|min:1',
            'order_item_ids.*' => 'required|exists:order_items,id',
        ]);

        $result = $this->tableService->splitTable(
            $table->id,
            $request->new_table_id,
            $request->order_item_ids
        );

        return response()->json([
            'success' => true,
            'message' => 'Table split successfully',
            'data' => $result,
        ]);
    }

    /**
     * Transfer order to another table
     */
    public function transfer(Request $request, Table $table)
    {
        $request->validate([
            'target_table_id' => 'required|exists:tables,id|different:' . $table->id,
        ]);

        $result = $this->tableService->transferTable(
            $table->id,
            $request->target_table_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Order transferred successfully',
            'data' => $result,
        ]);
    }

    /**
     * Get table statistics
     */
    public function statistics()
    {
        $stats = $this->tableService->getTableStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
