<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop the tables that are no longer needed
        DB::statement('DROP TABLE IF EXISTS audit_logs');
        DB::statement('DROP TABLE IF EXISTS daily_reports');
        DB::statement('DROP TABLE IF EXISTS delivery_orders');

        // Check if columns exist before trying to drop them
        $columnsToCheck = ['floor_id', 'position_x', 'position_y'];
        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn('tables', $column)) {
                DB::statement("ALTER TABLE `tables` DROP COLUMN `{$column}`");
            }
        }

        // Drop floors table
        DB::statement('DROP TABLE IF EXISTS floors');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive migration, reverse is complex
        // Recreate if needed manually
    }
};
