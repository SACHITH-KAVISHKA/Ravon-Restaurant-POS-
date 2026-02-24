<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Creates two log tables for cashier-side stock changes:
     * 1. cashier_fg_stock_logs - Finished Goods stock change logs
     * 2. cashier_rm_stock_logs - Raw Material stock change logs
     */
    public function up(): void
    {
        // Finished Goods Stock Log Table
        Schema::create('cashier_fg_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_stock_item_id')->constrained()->onDelete('cascade');
            $table->string('item_name'); // Denormalized for quick display
            $table->string('item_code')->nullable();
            $table->enum('action_type', [
                'sale_deduct',       // Deducted when order is paid
                'sale_restore',      // Restored when paid order is deleted
                'transfer_in',       // Received from supervisor transfer
                'adjustment',        // Manual stock adjustment
                'void_restore',      // Restored when items are voided (if applicable)
            ]);
            $table->decimal('quantity_before', 10, 3);
            $table->decimal('quantity_after', 10, 3);
            $table->decimal('quantity_changed', 10, 3); // Positive for IN, negative for OUT
            $table->string('reference_type')->nullable(); // 'order', 'transfer', 'adjustment'
            $table->string('reference_id')->nullable();   // Order number, transfer number, adjustment ID
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('main_stock_item_id');
            $table->index('action_type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });

        // Raw Material Stock Log Table
        Schema::create('cashier_rm_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_stock_item_id')->constrained()->onDelete('cascade');
            $table->string('item_name'); // Denormalized for quick display
            $table->string('item_code')->nullable();
            $table->enum('action_type', [
                'sale_deduct',       // Deducted when order is paid (recipe-based)
                'sale_restore',      // Restored when paid order is deleted
                'transfer_in',       // Received from supervisor transfer
                'adjustment',        // Manual stock adjustment
            ]);
            $table->decimal('quantity_before', 10, 3);
            $table->decimal('quantity_after', 10, 3);
            $table->decimal('quantity_changed', 10, 3); // Positive for IN, negative for OUT
            $table->string('reference_type')->nullable(); // 'order', 'transfer', 'adjustment'
            $table->string('reference_id')->nullable();   // Order number, transfer number, adjustment ID
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('main_stock_item_id');
            $table->index('action_type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_fg_stock_logs');
        Schema::dropIfExists('cashier_rm_stock_logs');
    }
};
