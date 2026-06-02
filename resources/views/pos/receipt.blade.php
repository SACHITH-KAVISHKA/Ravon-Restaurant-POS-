<!DOCTYPE html>
<html>

<head>
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Thermal printer page settings - 80mm width, AUTO height for dynamic content */
        @page {
            size: 80mm auto;
            /* Width fixed at 80mm, height expands automatically */
            margin: 0;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            /* Fixed font size - will NOT scale */
            line-height: 1.4;
            padding: 2mm 6mm 2mm 2mm;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            background: white;
            color: black;
            /* Prevent font size scaling during print */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 18px;
            /* Fixed font size */
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header div {
            font-size: 11px;
            /* Fixed font size */
        }

        .info {
            margin-bottom: 12px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
            /* Fixed font size */
        }

        .items {
            margin-bottom: 12px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .item {
            margin-bottom: 8px;
            page-break-inside: avoid;
            /* Prevent item from breaking across pages */
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
            /* Fixed font size */
            word-wrap: break-word;
        }

        .item-header span:first-child {
            max-width: 70%;
            word-wrap: break-word;
        }

        .item-details {
            font-size: 10px;
            /* Fixed font size */
            color: #333;
            margin-left: 8px;
            margin-top: 2px;
        }

        .totals {
            margin-bottom: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
            /* Fixed font size */
        }

        .total-row.grand {
            font-weight: bold;
            font-size: 13px;
            /* Fixed font size */
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }

        .payment {
            margin-bottom: 12px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            /* Fixed font size */
            margin-top: 15px;
            padding-top: 8px;
        }

        .footer p {
            margin: 3px 0;
        }

        /* Print-specific styles */
        @media print {

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 2mm 6mm 2mm 2mm;
                /* Ensure content flows naturally without scaling */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* Ensure items don't break awkwardly */
            .item {
                page-break-inside: avoid;
            }

            /* Prevent any automatic font scaling */
            * {
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        /* Screen preview styles */
        @media screen {
            body {
                padding: 20px;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>RAVON BAKERS</h1>
        <div>Jayawardena Holdings (Pvt) Ltd</div>
        <div>No 282/A/2, Kothalawala, Kaduwela</div>
        <div>Tel: +94 74 200 6007</div>
        @if(!empty($vatRegNo))
            <div>VAT Reg No: {{ $vatRegNo }}</div>
        @endif
    </div>

    <div class="info">
        <div class="info-row">
            <span>Customer:</span>
            <span>{{ $order->customer_name ?: 'Cash Customer' }}</span>
        </div>
        <div class="info-row">
            <span>VAT No:</span>
            <span>{{ !empty($order->customer_vat_number) ? $order->customer_vat_number : 'Not Eligible' }}</span>
        </div>
        <div class="info-row">
            <span>Invoice #:</span>
            <span>{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span>Date:</span>
            <span>{{ ($order->completed_at ?? now())->format('d/m/Y H:i:s') }}</span>
        </div>
        @if($order->table)
            <div class="info-row">
                <span>Table:</span>
                <span>{{ $order->table->table_number }}</span>
            </div>
        @endif
        <div class="info-row">
            <span>Order Type:</span>
            <span>{{ $order->order_type === 'dine_in' ? 'Dine In' : ($order->order_type === 'takeaway' ? 'Take Away' : ($order->order_type === 'pickme' ? 'PickMe Food' : ($order->order_type === 'uber_eats' ? 'Uber Eats' : ucwords(str_replace('_', ' ', $order->order_type))))) }}</span>
        </div>
        @if($order->order_type === 'pickme' && $order->pickme_ref_number)
            <div class="info-row">
                <span>PickMe Ref:</span>
                <span>{{ $order->pickme_ref_number }}</span>
            </div>
        @endif
        <div class="info-row">
            <span>Cashier:</span>
            <span>{{ $order->waiter ? $order->waiter->name : 'N/A' }}</span>
        </div>
    </div>

    @php
        $taxService   = app(\App\Services\TaxService::class);
        $activeItems  = $order->orderItems->where('status', '!=', 'deleted');
        $orderTax     = $taxService->calculateOrderTax(collect($activeItems)->values());
        $ssclRate = $taxService->getSsclRate();
        $vatRate = $taxService->getVatRate();
    @endphp

    <div class="items">
        {{-- IMPORTANT: Loop through ALL items without any limit - this ensures complete receipts --}}
        @foreach($activeItems as $item)
            @php
                $vatApplicable  = $item->item?->vat_available  ?? true;
                $ssclApplicable = $item->item?->sscl_available ?? true;
                $itemTax        = $taxService->calculateItemTax(
                    (float) $item->unit_price,
                    (int)   $item->quantity,
                    $vatApplicable,
                    $ssclApplicable
                );
                $baseUnitPrice  = $item->quantity > 0
                    ? $itemTax['base_amount'] / $item->quantity
                    : 0;
            @endphp
            <div class="item">
                <div class="item-header">
                    <span>{{ $item->quantity }} x {{ $item->item->name }}</span>
                    <span>{{ number_format($itemTax['base_amount'], 2) }}</span>
                </div>
                <div class="item-details">
                    @ Rs. {{ number_format($baseUnitPrice, 2) }} each
                </div>
                @if($item->modifiers->count() > 0)
                    @php
                        $nonPortionModifiers = \App\Helpers\PrintHelper::filterPortionModifiers($item->modifiers);
                    @endphp
                    @foreach($nonPortionModifiers as $modifier)
                        <div class="item-details">
                            + {{ $modifier->modifier ? $modifier->modifier->name : 'Modifier' }} (Rs.
                            {{ number_format($modifier->price_adjustment, 2) }})
                        </div>
                    @endforeach
                @endif
                @if($item->special_instructions)
                    <div class="item-details">
                        Note: {{ $item->special_instructions }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="totals">
        <div class="total-row">
            <span>Sub Total (Base):</span>
            <span>Rs. {{ number_format($orderTax['subtotal'], 2) }}</span>
        </div>
        <div class="total-row">
            <span>SSCL ({{ $ssclRate }}%)</span>
            <span>{{ number_format($orderTax['sscl_amount'], 2) }}</span>
        </div>
        <div class="total-row">
            <span>VAT ({{ $vatRate }}%)</span>
            <span>{{ number_format($orderTax['vat_amount'], 2) }}</span>
        </div>
        @if($orderTax['sscl_amount'] > 0)
            <div class="total-row">
                <span>SSCL ({{ $taxService->getSsclRate() }}%):</span>
                <span>Rs. {{ number_format($orderTax['sscl_amount'], 2) }}</span>
            </div>
        @endif
        @if($orderTax['vat_amount'] > 0)
            <div class="total-row">
                <span>VAT ({{ $taxService->getVatRate() }}%):</span>
                <span>Rs. {{ number_format($orderTax['vat_amount'], 2) }}</span>
            </div>
        @endif
        <div class="total-row grand">
            <span>TOTAL:</span>
            <span>Rs. {{ number_format($order->total_amount, 2) }}</span>
        </div>
    </div>

    @if($order->payment)
        <div class="payment">
            <div class="total-row">
                <span>Payment Method:</span>
                <span>{{ strtoupper($order->payment->payment_method) }}</span>
            </div>
            <div class="total-row">
                <span>Amount Paid:</span>
                <span>Rs. {{ number_format($order->payment->amount_paid, 2) }}</span>
            </div>
            @if($order->payment->change_amount > 0)
                <div class="total-row">
                    <span>Change:</span>
                    <span>Rs. {{ number_format($order->payment->change_amount, 2) }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="footer">
        <p>Thank you for dining with us!</p>
        <p>Visit again soon</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Print
            Receipt</button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>

    <script>
        // Auto print on load
        window.onload = function () {
            window.print();
        }
    </script>
</body>

</html>