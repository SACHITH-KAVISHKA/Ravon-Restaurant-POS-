<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add VAT and SSCL breakdown columns to the orders table.
     *
     * orders.subtotal     — net base amount (pre-tax)
     * orders.sscl_amount  — Social Security Contribution Levy
     * orders.vat_amount   — Value Added Tax
     * orders.tax_amount   — sscl_amount + vat_amount (already existed, repurposed)
     * orders.total_amount — gross inclusive amount (subtotal + tax_amount)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat_amount', 10, 2)->default(0.00)->after('tax_amount');
            $table->decimal('sscl_amount', 10, 2)->default(0.00)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vat_amount', 'sscl_amount']);
        });
    }
};
