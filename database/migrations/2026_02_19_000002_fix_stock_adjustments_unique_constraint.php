<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fix: adjustment_id should NOT be unique per row.
     * Multiple items share the same adjustment_id (batch).
     * Instead, use composite unique on (adjustment_id + main_stock_item_id).
     */
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            // Drop the unique constraint on adjustment_id
            $table->dropUnique('stock_adjustments_adjustment_id_unique');

            // Add regular index on adjustment_id (for lookups)
            $table->index('adjustment_id', 'stock_adj_adjustment_id_idx');

            // Add composite unique: same item can't appear twice in the same adjustment
            $table->unique(['adjustment_id', 'main_stock_item_id'], 'stock_adj_id_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropUnique('stock_adj_id_item_unique');
            $table->dropIndex('stock_adj_adjustment_id_idx');
            $table->unique('adjustment_id', 'stock_adjustments_adjustment_id_unique');
        });
    }
};
