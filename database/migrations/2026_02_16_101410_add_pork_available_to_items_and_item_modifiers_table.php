<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add pork_available to items table (for main items without portions)
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('pork_available')->default(false)->after('is_stock_count');
        });

        // Add pork_available to item_modifiers table (for items with portions)
        Schema::table('item_modifiers', function (Blueprint $table) {
            $table->boolean('pork_available')->default(false)->after('is_active');
        });

        // Add excluded_ingredients to order_items table (JSON field to store excluded ingredient IDs)
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('excluded_ingredients')->nullable()->after('special_instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('pork_available');
        });

        Schema::table('item_modifiers', function (Blueprint $table) {
            $table->dropColumn('pork_available');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('excluded_ingredients');
        });
    }
};
