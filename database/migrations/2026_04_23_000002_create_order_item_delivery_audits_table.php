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
        Schema::create('order_item_delivery_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('action_type', 64);
            $table->integer('old_value');
            $table->integer('new_value');
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_item_id', 'created_at']);
            $table->index(['supervisor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_delivery_audits');
    }
};
