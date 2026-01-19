<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add linked_item_id and linked_item_modifier_id to main_stock_items for Finished Goods
     * This allows linking FG stock items to menu items by ID instead of name matching
     */
    public function up(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            // Link to menu item (for finished goods)
            $table->foreignId('linked_item_id')->nullable()->after('item_type')->constrained('items')->onDelete('set null');
            // Link to specific portion/modifier (optional)
            $table->foreignId('linked_item_modifier_id')->nullable()->after('linked_item_id')->constrained('item_modifiers')->onDelete('set null');

            // Index for faster lookups
            $table->index(['linked_item_id', 'linked_item_modifier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->dropForeign(['linked_item_id']);
            $table->dropForeign(['linked_item_modifier_id']);
            $table->dropIndex(['linked_item_id', 'linked_item_modifier_id']);
            $table->dropColumn(['linked_item_id', 'linked_item_modifier_id']);
        });
    }
};
