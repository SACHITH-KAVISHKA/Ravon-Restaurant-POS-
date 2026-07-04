<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft-delete status flag to main stock items.
     * status = 1 => active, status = 0 => deleted (soft delete).
     */
    public function up(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('is_active');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stock_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
