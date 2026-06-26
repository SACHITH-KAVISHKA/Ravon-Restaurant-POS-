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
        Schema::table('items', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('display_order');
        });

        Schema::table('item_modifiers', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('item_modifiers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
