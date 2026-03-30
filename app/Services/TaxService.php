<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * TaxService — Sri Lanka backward tax calculation (VAT + SSCL).
 *
 * Item prices stored in the database are TAX-INCLUSIVE.
 *
 * Stacking order (as per Sri Lanka tax law):
 *   Base Price
 *   + SSCL  (applied on Base Price)
 *   = VAT Base
 *   + VAT   (applied on VAT Base = Base + SSCL)
 *   = Final Inclusive Price
 *
 * Backward formula:
 *   TaxFactor = (1 + SSCLRate) × (1 + VATRate)
 *   BasePrice = InclusivePrice / TaxFactor
 *   SSCLAmount = BasePrice × SSCLRate
 *   VATAmount  = (BasePrice + SSCLAmount) × VATRate
 */
class TaxService
{
    private float $vatRate;
    private float $ssclRate;

    public function __construct()
    {
        $this->loadRates();
    }

    /**
     * Load VAT and SSCL rates from the settings table.
     * Falls back to 0 if settings are not configured.
     */
    private function loadRates(): void
    {
        $setting = Setting::first();

        $this->vatRate  = $setting ? (float) ($setting->vat  ?? 0) : 0.0;
        $this->ssclRate = $setting ? (float) ($setting->sscl ?? 0) : 0.0;
    }

    /**
     * Return the currently loaded VAT rate (as a percentage, e.g. 18.0).
     */
    public function getVatRate(): float
    {
        return $this->vatRate;
    }

    /**
     * Return the currently loaded SSCL rate (as a percentage, e.g. 2.5).
     */
    public function getSsclRate(): float
    {
        return $this->ssclRate;
    }

    /**
     * Calculate taxes for a single line item using backward (reverse) calculation.
     *
     * @param  float $unitPrice      Tax-inclusive unit price stored in the database.
     * @param  int   $quantity       Number of units sold.
     * @param  bool  $vatApplicable  Whether VAT applies to this item.
     * @param  bool  $ssclApplicable Whether SSCL applies to this item.
     * @return array{
     *     unit_price:     float,  // original tax-inclusive unit price
     *     quantity:       int,
     *     inclusive_total: float, // unit_price × quantity
     *     base_amount:    float,  // net pre-tax amount for the line
     *     sscl_amount:    float,  // SSCL for the line
     *     vat_amount:     float,  // VAT for the line
     *     tax_amount:     float,  // sscl_amount + vat_amount
     *     total_amount:   float,  // same as inclusive_total (sanity check)
     * }
     */
    public function calculateItemTax(
        float $unitPrice,
        int   $quantity,
        bool  $vatApplicable  = true,
        bool  $ssclApplicable = true
    ): array {
        $inclusiveTotal = $unitPrice * $quantity;

        // Determine effective rates for this item
        $effectiveVat  = $vatApplicable  ? $this->vatRate  / 100 : 0.0;
        $effectiveSscl = $ssclApplicable ? $this->ssclRate / 100 : 0.0;

        // TaxFactor = (1 + SSCL%) × (1 + VAT%)
        $taxFactor = (1 + $effectiveSscl) * (1 + $effectiveVat);

        // Avoid division by zero when both rates are 0
        if ($taxFactor == 0) {
            $taxFactor = 1.0;
        }

        // Backward calculation — work with full precision; round only final values
        $baseAmount = $inclusiveTotal / $taxFactor;
        $ssclAmount = $baseAmount * $effectiveSscl;
        $vatAmount  = ($baseAmount + $ssclAmount) * $effectiveVat;
        $taxAmount  = $ssclAmount + $vatAmount;

        return [
            'unit_price'      => $unitPrice,
            'quantity'        => $quantity,
            'inclusive_total' => round($inclusiveTotal, 2),
            'base_amount'     => round($baseAmount, 2),
            'sscl_amount'     => round($ssclAmount, 2),
            'vat_amount'      => round($vatAmount, 2),
            'tax_amount'      => round($taxAmount, 2),
            'total_amount'    => round($inclusiveTotal, 2), // equals inclusive_total
        ];
    }

    /**
     * Calculate and accumulate taxes across all active order items.
     *
     * Expects each item to have an `item` relation loaded (for vat_available
     * and sscl_available flags).
     *
     * @param  Collection|\Illuminate\Database\Eloquent\Collection $orderItems
     * @return array{
     *     subtotal:     float,  // total net base amount (pre-tax)
     *     sscl_amount:  float,  // accumulated SSCL across all items
     *     vat_amount:   float,  // accumulated VAT across all items
     *     tax_amount:   float,  // sscl_amount + vat_amount
     *     total_amount: float,  // subtotal + tax_amount (= sum of inclusive prices)
     * }
     */
    public function calculateOrderTax(Collection $orderItems): array
    {
        $totalBase  = 0.0;
        $totalSscl  = 0.0;
        $totalVat   = 0.0;
        $totalInclusive = 0.0;

        foreach ($orderItems as $orderItem) {
            // Read flags from the related Item model; default to true if not available
            $vatApplicable  = $orderItem->item->vat_available  ?? true;
            $ssclApplicable = $orderItem->item->sscl_available ?? true;

            $unitPrice = (float) $orderItem->unit_price;
            $quantity  = (int)   $orderItem->quantity;

            // Use high-precision intermediate values before rounding
            $effectiveVat  = $vatApplicable  ? $this->vatRate  / 100 : 0.0;
            $effectiveSscl = $ssclApplicable ? $this->ssclRate / 100 : 0.0;

            $taxFactor    = (1 + $effectiveSscl) * (1 + $effectiveVat);
            $taxFactor    = $taxFactor ?: 1.0;

            $inclusiveTotal = $unitPrice * $quantity;
            $baseAmount     = $inclusiveTotal / $taxFactor;
            $ssclAmount     = $baseAmount * $effectiveSscl;
            $vatAmount      = ($baseAmount + $ssclAmount) * $effectiveVat;

            $totalInclusive += $inclusiveTotal;
            $totalBase      += $baseAmount;
            $totalSscl      += $ssclAmount;
            $totalVat       += $vatAmount;
        }

        $totalTax = $totalSscl + $totalVat;

        return [
            'subtotal'     => round($totalBase, 2),
            'sscl_amount'  => round($totalSscl, 2),
            'vat_amount'   => round($totalVat, 2),
            'tax_amount'   => round($totalTax, 2),
            'total_amount' => round($totalInclusive, 2),
        ];
    }
}
