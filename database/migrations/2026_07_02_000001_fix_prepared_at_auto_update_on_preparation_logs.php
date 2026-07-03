<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `prepared_at` was declared as a TIMESTAMP column with no explicit default,
     * making it the first such column in the table. On MySQL/MariaDB servers where
     * `explicit_defaults_for_timestamp` is disabled, MySQL implicitly attaches
     * `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` to that column, so any
     * later UPDATE to the row (e.g. marking the item delivered) silently overwrote
     * `prepared_at` with the current time even though it was never in the SET list.
     * DATETIME columns are never subject to this implicit MySQL behaviour.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE order_item_preparation_logs MODIFY prepared_at DATETIME NOT NULL');

        // Repair rows already corrupted by the bug: for delivered logs where prepared_at
        // was clobbered to equal delivered_at, reconstruct the original prepared_at using
        // the preparation_minutes value, which was computed and stored correctly beforehand.
        DB::table('order_item_preparation_logs')
            ->whereNotNull('delivered_at')
            ->whereNotNull('preparation_minutes')
            ->whereColumn('prepared_at', '=', 'delivered_at')
            ->update([
                'prepared_at' => DB::raw('DATE_SUB(delivered_at, INTERVAL preparation_minutes MINUTE)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE order_item_preparation_logs MODIFY prepared_at TIMESTAMP NOT NULL');
    }
};
