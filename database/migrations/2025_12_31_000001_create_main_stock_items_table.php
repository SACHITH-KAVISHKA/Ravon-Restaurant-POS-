<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Main Stock Items - Independent inventory items (raw materials, finished goods, etc.)
     */
    public function up(): void
    {
        Schema::create('main_stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('item_name', 255);
            $table->enum('unit_type', ['kilogram', 'gram', 'liter', 'milliliter', 'quantity'])->default('quantity');
            $table->enum('item_type', ['raw_material', 'finished_good', 'other'])->default('other');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('item_type');
            $table->index('is_active');
            $table->index(['item_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_stock_items');
    }
};
