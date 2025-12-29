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
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_modifier_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('price_type', 50); // 'pickme', 'ubereats', etc. (NOT 'default' - default prices stay in items/item_modifiers tables)
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Indexes
            $table->index(['item_id', 'price_type'], 'idx_item_price_type');
            $table->index(['item_modifier_id', 'price_type'], 'idx_modifier_price_type');
            
            // Unique constraint: one price per type per item/modifier combination
            $table->unique(['item_id', 'item_modifier_id', 'price_type'], 'unique_item_modifier_price_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};
