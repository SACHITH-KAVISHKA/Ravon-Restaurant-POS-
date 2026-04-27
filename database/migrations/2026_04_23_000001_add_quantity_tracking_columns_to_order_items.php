<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'latest_added_quantity')) {
                $table->integer('latest_added_quantity')->default(0)->after('quantity');
            }

            if (!Schema::hasColumn('order_items', 'delivered_quantity')) {
                $table->integer('delivered_quantity')->default(0)->after('latest_added_quantity');
            }
        });

        DB::table('order_items')
            ->whereNull('latest_added_quantity')
            ->update(['latest_added_quantity' => DB::raw('quantity')]);

        DB::table('order_items')
            ->whereNull('delivered_quantity')
            ->update([
                'delivered_quantity' => DB::raw("CASE WHEN status IN ('served', 'delivered') THEN quantity ELSE 0 END"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'delivered_quantity')) {
                $table->dropColumn('delivered_quantity');
            }

            if (Schema::hasColumn('order_items', 'latest_added_quantity')) {
                $table->dropColumn('latest_added_quantity');
            }
        });
    }
};