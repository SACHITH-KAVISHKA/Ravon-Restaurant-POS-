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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->onDelete('cascade');
            $table->string('table_number', 20);
            $table->integer('capacity')->default(4);
            $table->enum('status', ['available', 'ordered', 'serving', 'bill_requested'])->default('available');
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->unique(['floor_id', 'table_number'], 'unique_floor_table');
            $table->index(['floor_id', 'status'], 'idx_floor_status');
            $table->index('current_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
