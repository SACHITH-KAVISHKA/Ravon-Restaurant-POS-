<?php

namespace App\Services;

use App\Models\Table;
use App\Models\TableMerge;
use App\Models\Floor;
use Illuminate\Support\Facades\DB;
use Exception;

class TableService
{
    /**
     * Get all floors with their tables.
     */
    public function getFloorsWithTables()
    {
        return Floor::active()
            ->ordered()
            ->with(['tables' => function ($query) {
                $query->active()->orderBy('table_number');
            }])
            ->get();
    }

    /**
     * Get table with current order details.
     */
    public function getTableWithOrder($tableId)
    {
        return Table::with(['currentOrder.orderItems.item', 'floor'])
            ->findOrFail($tableId);
    }

    /**
     * Update table status.
     */
    public function updateStatus(Table $table, string $status): Table
    {
        $table->update(['status' => $status]);
        
        // Broadcast real-time update
        broadcast(new \App\Events\TableStatusUpdated($table));
        
        return $table->fresh();
    }

    /**
     * Merge tables.
     */
    public function mergeTables(int $masterTableId, array $tableIds, int $orderId): TableMerge
    {
        DB::beginTransaction();
        try {
            $masterTable = Table::findOrFail($masterTableId);
            
            foreach ($tableIds as $tableId) {
                if ($tableId == $masterTableId) continue;
                
                $table = Table::findOrFail($tableId);
                
                // Create merge record
                TableMerge::create([
                    'master_table_id' => $masterTableId,
                    'merged_table_id' => $tableId,
                    'order_id' => $orderId,
                    'merged_by' => auth()->id(),
                    'merged_at' => now(),
                ]);
                
                // Update merged table status
                $table->update(['status' => 'ordered']);
            }
            
            // Update master table
            $masterTable->update([
                'status' => 'ordered',
                'current_order_id' => $orderId,
            ]);
            
            DB::commit();
            
            // Broadcast
            broadcast(new \App\Events\TablesMerged($masterTable, $tableIds));
            
            return $masterTable;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Split table.
     */
    public function splitTable(int $tableId, array $itemsForNewTable): array
    {
        DB::beginTransaction();
        try {
            $originalTable = Table::with('currentOrder')->findOrFail($tableId);
            
            // Find available table
            $newTable = Table::available()
                ->where('floor_id', $originalTable->floor_id)
                ->firstOrFail();
            
            // Create new order for split
            $newOrder = app(OrderService::class)->createSplitOrder(
                $originalTable->currentOrder,
                $itemsForNewTable
            );
            
            // Update new table
            $newTable->update([
                'status' => 'ordered',
                'current_order_id' => $newOrder->id,
            ]);
            
            // Recalculate original order
            $originalTable->currentOrder->calculateTotal();
            
            DB::commit();
            
            return [
                'original_table' => $originalTable->fresh(),
                'new_table' => $newTable->fresh(),
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transfer table.
     */
    public function transferTable(int $fromTableId, int $toTableId): array
    {
        DB::beginTransaction();
        try {
            $fromTable = Table::with('currentOrder')->findOrFail($fromTableId);
            $toTable = Table::findOrFail($toTableId);
            
            if (!$toTable->isAvailable()) {
                throw new Exception('Target table is not available');
            }
            
            $order = $fromTable->currentOrder;
            
            // Update order
            $order->update(['table_id' => $toTableId]);
            
            // Update tables
            $fromTable->update([
                'status' => 'available',
                'current_order_id' => null,
            ]);
            
            $toTable->update([
                'status' => $fromTable->status,
                'current_order_id' => $order->id,
            ]);
            
            DB::commit();
            
            // Broadcast
            broadcast(new \App\Events\TableTransferred($fromTable, $toTable, $order));
            
            return [
                'from_table' => $fromTable->fresh(),
                'to_table' => $toTable->fresh(),
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get available tables on floor.
     */
    public function getAvailableTablesOnFloor(int $floorId)
    {
        return Table::available()
            ->where('floor_id', $floorId)
            ->orderBy('table_number')
            ->get();
    }

    /**
     * Get table statistics.
     */
    public function getTableStatistics()
    {
        return [
            'total' => Table::active()->count(),
            'available' => Table::available()->count(),
            'occupied' => Table::occupied()->count(),
            'by_status' => Table::active()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];
    }
}
