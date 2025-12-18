<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add item_modifier_id to stock_request_items table
     */
    public function up(): void
    {
        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_modifier_id')->nullable()->after('item_id');
            $table->foreign('item_modifier_id')->references('id')->on('item_modifiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->dropForeign(['item_modifier_id']);
            $table->dropColumn('item_modifier_id');
        });
    }
};
