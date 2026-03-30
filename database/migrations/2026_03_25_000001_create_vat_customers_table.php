<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('vat_number', 50)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->unique('vat_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_customers');
    }
};
