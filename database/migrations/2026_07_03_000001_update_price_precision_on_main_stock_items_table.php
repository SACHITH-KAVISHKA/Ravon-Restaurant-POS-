<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Allow prices with up to 5 decimal places (e.g. 0.00001).
     */
    public function up(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->decimal('price', 15, 5)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->change();
        });
    }
};
