<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds normalization column for raw material items.
     * When a normalization value is set, the displayed quantity = actual quantity * normalization
     */
    public function up(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->decimal('normalization', 10, 4)->nullable()->after('quantity')
                ->comment('Multiplication factor for raw materials. Display qty = actual qty * normalization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->dropColumn('normalization');
        });
    }
};
