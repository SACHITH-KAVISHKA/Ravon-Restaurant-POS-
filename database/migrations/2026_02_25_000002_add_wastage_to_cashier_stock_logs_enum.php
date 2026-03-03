<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'wastage' to action_type enum in cashier stock logs tables.
     */
    public function up(): void
    {
        // Add 'wastage' to cashier_fg_stock_logs action_type enum
        DB::statement("ALTER TABLE cashier_fg_stock_logs MODIFY COLUMN action_type ENUM('sale_deduct', 'sale_restore', 'transfer_in', 'adjustment', 'void_restore', 'wastage')");

        // Add 'wastage' to cashier_rm_stock_logs action_type enum
        DB::statement("ALTER TABLE cashier_rm_stock_logs MODIFY COLUMN action_type ENUM('sale_deduct', 'sale_restore', 'transfer_in', 'adjustment', 'wastage')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'wastage' from cashier_fg_stock_logs action_type enum
        DB::statement("ALTER TABLE cashier_fg_stock_logs MODIFY COLUMN action_type ENUM('sale_deduct', 'sale_restore', 'transfer_in', 'adjustment', 'void_restore')");

        // Remove 'wastage' from cashier_rm_stock_logs action_type enum
        DB::statement("ALTER TABLE cashier_rm_stock_logs MODIFY COLUMN action_type ENUM('sale_deduct', 'sale_restore', 'transfer_in', 'adjustment')");
    }
};
