<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Item Recipes - Links menu items/portions to raw materials from MainStockItem
     */
    public function up(): void
    {
        Schema::create('item_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('item_modifier_id')->nullable()->constrained('item_modifiers')->onDelete('cascade');
            $table->foreignId('main_stock_item_id')->constrained('main_stock_items')->onDelete('cascade');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['item_id', 'item_modifier_id']);
            $table->index('main_stock_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_recipes');
    }
};
