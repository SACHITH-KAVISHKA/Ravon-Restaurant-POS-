<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cashier sub stock - stores raw materials transferred from supervisor
     */
    public function up(): void
    {
        Schema::create('cashier_sub_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_stock_item_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 3)->default(0);
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint: one record per main stock item
            $table->unique('main_stock_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_sub_stocks');
    }
};
