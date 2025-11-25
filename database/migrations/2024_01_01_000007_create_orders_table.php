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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'uber_eats', 'pickme']);
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('waiter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->integer('guest_count')->default(1);
            
            // Financial fields
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->string('discount_reason')->nullable();
            $table->decimal('service_charge', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            
            // Additional info
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Status timestamps
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index(['table_id', 'status'], 'idx_table_status');
            $table->index(['order_type', 'status'], 'idx_type_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
