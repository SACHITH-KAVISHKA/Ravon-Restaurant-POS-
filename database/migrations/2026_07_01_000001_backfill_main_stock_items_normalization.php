<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill missing normalization values in main_stock_items.
     */
    public function up(): void
    {
        if (! Schema::hasTable('main_stock_items') || ! Schema::hasColumn('main_stock_items', 'normalization')) {
            return;
        }

        DB::table('main_stock_items')
            ->whereNull('normalization')
            ->update(['normalization' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * A safe rollback is not possible because this migration does not track
     * which rows originally contained NULL before the backfill.
     */
    public function down(): void
    {
        // Intentionally left empty because a safe rollback is not possible.
    }
};