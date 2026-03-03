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
        Schema::create('wastages', function (Blueprint $table) {
            $table->id();
            $table->string('wastage_id', 50)->index();
            $table->timestamp('wastage_date')->useCurrent();
            $table->foreignId('main_stock_item_id')->constrained('main_stock_items')->onDelete('cascade');
            $table->string('item_name', 255);
            $table->string('item_code', 50)->nullable();
            $table->enum('item_type', ['raw_material', 'finished_good']);
            $table->decimal('quantity_before', 15, 3);
            $table->decimal('quantity_wasted', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('wastage_amount', 15, 2)->default(0);
            $table->string('reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('wastage_date');
            $table->index('item_type');
            $table->index(['main_stock_item_id', 'wastage_date']);
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wastages');
    }
};
