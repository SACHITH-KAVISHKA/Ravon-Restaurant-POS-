<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('void_records', function (Blueprint $table) {
            $table->id();
            $table->string('void_number')->unique(); // Unique void reference number
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('order_number'); // Store order number for quick reference
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('set null');
            $table->string('item_name'); // Item name at time of void
            $table->string('item_code')->nullable(); // Item code for reference
            $table->integer('voided_quantity'); // How many were voided
            $table->decimal('unit_price', 10, 2); // Price per item at time of void
            $table->decimal('voided_amount', 10, 2); // Total amount voided (qty * price)
            $table->foreignId('cashier_id')->constrained('users')->onDelete('cascade'); // Who was the cashier
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade'); // Who approved
            $table->string('supervisor_name'); // Supervisor name at time of void
            $table->string('reason')->nullable(); // Optional reason for void
            $table->string('table_number')->nullable(); // Table number if dine-in
            $table->string('cancel_kot_number')->nullable(); // Cancel KOT reference
            $table->timestamps();

            // Indexes for faster queries
            $table->index('order_id');
            $table->index('item_id');
            $table->index('cashier_id');
            $table->index('supervisor_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('void_records');
    }
};
