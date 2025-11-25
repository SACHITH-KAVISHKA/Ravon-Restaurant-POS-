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
        Schema::create('kot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kot_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->integer('quantity');
            $table->text('special_instructions')->nullable();
            $table->json('modifiers')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index('kot_id');
            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kot_items');
    }
};
