<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'served', 'delivered', 'cancelled', 'deleted') DEFAULT 'preparing'");

        // Backfill only active pending items to the new default state.
        DB::table('order_items')
            ->where('status', 'pending')
            ->update(['status' => 'preparing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert delivered items to served before removing the enum value.
        DB::table('order_items')
            ->where('status', 'delivered')
            ->update(['status' => 'served']);

        DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled', 'deleted') DEFAULT 'pending'");
    }
};
