<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add item_modifier_id to order_items for ID-based stock deduction
     * This allows linking order items to specific portions/modifiers by ID instead of name matching
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Link to specific portion/modifier (optional - null for items without portions)
            $table->foreignId('item_modifier_id')->nullable()->after('item_id')->constrained('item_modifiers')->onDelete('set null');

            // Index for faster lookups during stock deduction
            $table->index(['item_id', 'item_modifier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['item_modifier_id']);
            $table->dropIndex(['item_id', 'item_modifier_id']);
            $table->dropColumn('item_modifier_id');
        });
    }
};
