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
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('order_number', 50)->nullable(); // Store order number for reference even after deletion

            // Action tracking
            $table->enum('action', [
                'created',           // Order was created
                'status_changed',    // Order status was changed
                'updated',           // Order details were modified
                'deleted',           // Order was deleted/cancelled
                'item_added',        // Item was added to order
                'item_removed',      // Item was removed from order
                'item_modified',     // Item quantity/details modified
                'payment_added',     // Payment was added
                'payment_updated',   // Payment was updated
                'table_changed',     // Table was changed/transferred
                'merged',            // Order was merged with another
                'kot_printed',       // KOT was printed
                'discount_applied',  // Discount was applied
                'restored'           // Order was restored
            ]);

            // Status tracking
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();

            // Reason for the change
            $table->text('reason')->nullable();

            // Store the changes made (before and after values)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Additional context
            $table->text('description')->nullable(); // Human readable description

            // User tracking
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');

            // IP and device info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast querying
            $table->index('order_id');
            $table->index('order_number');
            $table->index('action');
            $table->index('performed_by');
            $table->index('created_at');
            $table->index(['order_id', 'action'], 'idx_order_action');
            $table->index(['order_id', 'created_at'], 'idx_order_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};
