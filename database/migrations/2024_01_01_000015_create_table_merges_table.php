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
        Schema::create('table_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('merged_table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('merged_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('merged_at');
            $table->timestamp('unmerged_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_table_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_merges');
    }
};
