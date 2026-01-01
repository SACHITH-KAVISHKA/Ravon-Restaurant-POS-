<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Main Stock Transaction Items - Store individual items for each transaction
     */
    public function up(): void
    {
        Schema::create('main_stock_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_stock_transaction_id')->constrained('main_stock_transactions')->onDelete('cascade');
            $table->foreignId('main_stock_item_id')->constrained('main_stock_items')->onDelete('cascade');
            $table->decimal('quantity', 15, 3)->comment('Quantity added/removed');
            $table->decimal('quantity_before', 15, 3)->comment('Stock quantity before transaction');
            $table->decimal('quantity_after', 15, 3)->comment('Stock quantity after transaction');
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['main_stock_transaction_id', 'main_stock_item_id'], 'txn_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_stock_transaction_items');
    }
};
