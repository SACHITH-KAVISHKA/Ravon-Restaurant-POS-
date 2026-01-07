<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stock transfers from supervisor (main stock) to cashier (sub stock)
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null'); // Target cashier
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('cashier_notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('main_stock_item_id')->constrained()->onDelete('cascade');
            $table->string('item_name'); // Store item name at time of transfer
            $table->decimal('quantity', 10, 3); // Transfer quantity
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
