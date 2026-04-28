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
            if (!Schema::hasColumn('order_items', 'last_delivered_at')) {
                $table->timestamp('last_delivered_at')->nullable()->after('delivered_at');
            }
        });

        DB::table('order_items')
            ->whereColumn('delivered_quantity', 'quantity')
            ->where('quantity', '>', 0)
            ->whereNull('last_delivered_at')
            ->update(['last_delivered_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'last_delivered_at')) {
                $table->dropColumn('last_delivered_at');
            }
        });
    }
};