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
            $table->timestamp('preparing_at')->nullable()->after('status');
            $table->timestamp('delivered_at')->nullable()->after('preparing_at');
        });

        // Existing rows were effectively "preparing" from creation time.
        DB::table('order_items')
            ->whereNull('preparing_at')
            ->update(['preparing_at' => DB::raw('created_at')]);

        // Backfill delivered timestamp for already delivered rows.
        DB::table('order_items')
            ->where('status', 'delivered')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['preparing_at', 'delivered_at']);
        });
    }
};
