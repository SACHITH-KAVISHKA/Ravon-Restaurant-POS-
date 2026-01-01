<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove main_stock_item_id column as items are now stored in main_stock_transaction_items table.
     */
    public function up(): void
    {
        Schema::table('main_stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['main_stock_item_id']);
            $table->dropColumn('main_stock_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stock_transactions', function (Blueprint $table) {
            $table->foreignId('main_stock_item_id')->after('transaction_number')->constrained('main_stock_items')->onDelete('cascade');
        });
    }
};
