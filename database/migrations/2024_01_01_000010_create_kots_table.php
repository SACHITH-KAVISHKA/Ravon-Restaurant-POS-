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
        Schema::create('kots', function (Blueprint $table) {
            $table->id();
            $table->string('kot_number', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('kitchen_station_id')->constrained()->onDelete('cascade');
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('waiter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('kot_number');
            $table->index(['kitchen_station_id', 'status'], 'idx_station_status');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kots');
    }
};
