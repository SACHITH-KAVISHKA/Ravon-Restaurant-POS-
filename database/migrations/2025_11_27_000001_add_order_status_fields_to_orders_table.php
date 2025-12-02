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
        Schema::table('orders', function (Blueprint $table) {
            // Change status to support open/closed states
            // pending -> order created but not placed
            // open -> order placed (KOT sent) but not paid
            // completed -> payment done
            // cancelled -> order cancelled

            // Add fields to track order progression
            $table->timestamp('placed_at')->nullable()->after('created_at');
            $table->boolean('is_paid')->default(false)->after('status');

            // Add print tracking
            $table->integer('kot_print_count')->default(0)->after('is_paid');
            $table->timestamp('last_kot_printed_at')->nullable()->after('kot_print_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['placed_at', 'is_paid', 'kot_print_count', 'last_kot_printed_at']);
        });
    }
};
