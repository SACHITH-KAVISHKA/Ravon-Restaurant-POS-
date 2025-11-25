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
        Schema::create('item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('type', ['level', 'addon', 'special_instruction']);
            $table->decimal('price_adjustment', 8, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['item_id', 'is_active'], 'idx_item_modifier_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_modifiers');
    }
};
