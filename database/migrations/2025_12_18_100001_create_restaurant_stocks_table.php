<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restaurant Stock - Cashier's available stock at the restaurant
     */
    public function up(): void
    {
        Schema::create('restaurant_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->unsignedBigInteger('item_modifier_id')->nullable();
            $table->foreign('item_modifier_id')->references('id')->on('item_modifiers')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit')->default('pcs')->comment('Unit of measurement (pcs, kg, liters, etc.)');
            $table->text('notes')->nullable();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint for item + modifier combination
            $table->unique(['item_id', 'item_modifier_id'], 'restaurant_stocks_item_modifier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_stocks');
    }
};
