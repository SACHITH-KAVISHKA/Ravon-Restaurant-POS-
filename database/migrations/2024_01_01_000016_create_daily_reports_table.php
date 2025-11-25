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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 12, 2)->default(0.00);
            $table->decimal('total_tax', 10, 2)->default(0.00);
            $table->decimal('total_discounts', 10, 2)->default(0.00);
            $table->decimal('cash_sales', 10, 2)->default(0.00);
            $table->decimal('card_sales', 10, 2)->default(0.00);
            $table->integer('cancelled_orders')->default(0);
            $table->integer('dine_in_orders')->default(0);
            $table->integer('takeaway_orders')->default(0);
            $table->integer('delivery_orders')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();

            // Indexes
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
