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
        Schema::table('payments', function (Blueprint $table) {
            // Add new payment amount columns
            $table->decimal('cash_amount', 10, 2)->default(0.00)->after('paid_amount');
            $table->decimal('card_amount', 10, 2)->default(0.00)->after('cash_amount');
            $table->decimal('credit_amount', 10, 2)->default(0.00)->after('card_amount');
            
            // Remove reference_number and notes columns
            $table->dropColumn(['reference_number', 'notes']);
        });

        // Update payment_method enum to include credit
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'mixed', 'credit'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add back reference_number and notes
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            
            // Remove new columns
            $table->dropColumn(['cash_amount', 'card_amount', 'credit_amount']);
        });

        // Revert payment_method enum
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'mixed'])->change();
        });
    }
};
