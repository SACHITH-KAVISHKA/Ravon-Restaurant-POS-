<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE item_modifiers MODIFY COLUMN type VARCHAR(100)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum if needed, but it's risky if we have new data.
        // For now, we can leave it as varchar or try to revert.
        // DB::statement("ALTER TABLE item_modifiers MODIFY COLUMN type ENUM('level', 'addon', 'special_instruction')");
    }
};
