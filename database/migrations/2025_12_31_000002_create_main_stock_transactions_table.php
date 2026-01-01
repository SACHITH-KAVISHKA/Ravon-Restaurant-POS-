<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Main Stock Transactions - Track all stock movements (additions, deductions, transfers)
     */
    public function up(): void
    {
        Schema::create('main_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('main_stock_item_id')->constrained('main_stock_items')->onDelete('cascade');
            $table->enum('transaction_type', ['stock_in', 'stock_out', 'adjustment', 'transfer_to_restaurant', 'return_from_restaurant', 'wastage', 'expired'])->default('stock_in');
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_before', 15, 3)->comment('Stock quantity before transaction');
            $table->decimal('quantity_after', 15, 3)->comment('Stock quantity after transaction');
            $table->string('reference_number', 100)->nullable()->comment('Invoice/PO number or reference');
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index(['main_stock_item_id', 'transaction_date'], 'stock_txn_item_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_stock_transactions');
    }
};
