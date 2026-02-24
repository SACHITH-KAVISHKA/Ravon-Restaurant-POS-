<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stock Adjustments - Track stock count adjustments (RM & Cashier stock)
     */
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_id', 50)->unique()->comment('Generated adjustment reference');
            $table->timestamp('adjustment_date')->useCurrent();
            $table->string('cashier_name', 100)->nullable()->comment('Who counted stock');
            $table->text('notes')->nullable();
            $table->foreignId('main_stock_item_id')->constrained('main_stock_items')->onDelete('cascade');
            $table->string('item_name', 255)->comment('Snapshot of item name at adjustment time');
            $table->decimal('system_qty', 15, 3)->comment('Stock in system before adjustment');
            $table->decimal('actual_qty', 15, 3)->comment('Physically counted qty');
            $table->decimal('variance_qty', 15, 3)->comment('actual - system');
            $table->decimal('price', 15, 2)->default(0)->comment('Price from main item price list');
            $table->decimal('variance_amount', 15, 2)->default(0)->comment('variance_qty * price');
            $table->enum('adjustment_type', ['IN', 'OUT'])->comment('IN = positive variance, OUT = negative');
            $table->enum('stock_location', ['RM', 'CASHIER'])->comment('Where stock was adjusted');
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('adjustment_date');
            $table->index('adjustment_type');
            $table->index('stock_location');
            $table->index(['main_stock_item_id', 'adjustment_date'], 'stock_adj_item_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
