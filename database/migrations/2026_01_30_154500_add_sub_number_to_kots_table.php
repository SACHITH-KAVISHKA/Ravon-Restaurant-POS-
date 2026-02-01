<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Adds a sub_number column to track KOT/BOT sub-tickets for the same order.
     * - sub_number = 0: Primary KOT/BOT (first items ordered)
     * - sub_number = 1, 2, 3...: Subsequent KOT/BOT when items are added later
     */
    public function up(): void
    {
        Schema::table('kots', function (Blueprint $table) {
            // Add sub_number column after kot_number
            // 0 = primary KOT/BOT, 1+ = subsequent additions
            $table->unsignedInteger('sub_number')->default(0)->after('kot_number');

            // Add index for efficient querying of KOTs by order
            $table->index(['order_id', 'kitchen_station_id', 'sub_number'], 'idx_order_station_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kots', function (Blueprint $table) {
            $table->dropIndex('idx_order_station_sub');
            $table->dropColumn('sub_number');
        });
    }
};
