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
        Schema::create('order_item_preparation_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');

            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_modifier_id')->nullable();

            $table->string('item_display_name', 255);

            $table->integer('quantity')->default(1);

            $table->enum('item_status', [
                'pending',
                'preparing',
                'ready',
                'served',
                'delivered',
                'cancelled',
                'deleted',
            ])->default('preparing');

            $table->timestamp('prepared_at');
            $table->timestamp('delivered_at')->nullable();

            $table->integer('preparation_minutes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('item_modifier_id')->references('id')->on('item_modifiers')->onDelete('set null');

            // Indexes for reporting queries
            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('item_id');
            $table->index(['item_id', 'item_modifier_id']);
            $table->index('item_status');
            $table->index('prepared_at');
            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_preparation_logs');
    }
};
