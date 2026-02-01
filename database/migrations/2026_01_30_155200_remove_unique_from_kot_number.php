<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Removes the unique constraint on kot_number since sub-KOTs
     * now share the same base kot_number (e.g., KOT-20260130-0001)
     * with different sub_numbers (0, 1, 2, etc.)
     */
    public function up(): void
    {
        Schema::table('kots', function (Blueprint $table) {
            // Drop the unique constraint on kot_number
            $table->dropUnique(['kot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kots', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->unique('kot_number');
        });
    }
};
