<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.35;
            padding: 2mm 6mm 2mm 2mm;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .text-center {
            text-align: center;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sub {
            font-size: 11px;
            margin-top: 2px;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: 700;
            margin: 10px 0 8px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin: 2px 0;
            font-size: 11px;
        }

        .label {
            white-space: nowrap;
        }

        .value {
            text-align: right;
            word-break: break-word;
        }

        .separator {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        .thin-separator {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .items-header {
            font-size: 12px;
            font-weight: 700;
        }

        .item-name {
            margin: 4px 0 2px;
            font-size: 12px;
            font-weight: 700;
        }

        .item-subline {
            margin-left: 8px;
            margin-bottom: 2px;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            font-size: 11px;
        }

        .grand-total {
            border-top: 2px solid #000;
            margin-top: 6px;
            padding-top: 4px;
            font-size: 12px;
            font-weight: 700;
        }

        .thanks {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .small-footer {
            margin-top: 4px;
            font-size: 10px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 2mm 6mm 2mm 2mm;
            }

            * {
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        @media screen {
            body {
                padding: 20px;
                max-width: 320px;
            }
        }
    </style>
</head>

<body>
    @php
        $taxService = app(\App\Services\TaxService::class);
        $activeItems = $order->orderItems->where('status', '!=', 'deleted');
        $orderTax = $taxService->calculateOrderTax(collect($activeItems)->values());

        $dateTime = $order->completed_at ?? now();
        $dateText = $dateTime->format('d/m/Y');
        $timeText = $dateTime->format('H:i:s');

        if ($order->table && $order->table->table_number) {
            $tableText = (string) $order->table->table_number;
        } elseif ($order->order_type === 'pickme' && $order->pickme_ref_number) {
            $tableText = 'PickMe - ' . $order->pickme_ref_number;
        } elseif ($order->order_type === 'pickme') {
            $tableText = 'PickMe Food';
        } elseif ($order->order_type === 'uber_eats') {
            $tableText = 'Uber Eats';
        } elseif ($order->order_type === 'delivery') {
            $tableText = 'Delivery';
        } else {
            $tableText = 'Take Away';
        }
    @endphp

    <div class="text-center">
        <div class="title">RAVON BAKERS</div>
        <div class="sub">Jayawardena Holdings (Pvt) Ltd</div>
        <div class="sub">No 282/A/2, Kothalawala, Kaduwela</div>
        <div class="sub">Tel: +94 74 200 6007</div>
        @if(!empty($vatRegNo))
            <div class="sub">VAT Reg No: {{ $vatRegNo }}</div>
        @endif
        <div class="invoice-title">INVOICE</div>
    </div>

    <div class="row">
        <span class="label">Customer :</span>
        <span class="value">{{ $order->customer_name ?: 'Cash Customer' }}</span>
    </div>
    <div class="row">
        <span class="label">VAT No :</span>
        <span class="value">{{ !empty($order->customer_vat_number) ? $order->customer_vat_number : 'Not Eligible' }}</span>
    </div>
    <div class="row">
        <span class="label">Invoice #</span>
        <span class="value">{{ $order->order_number }}</span>
    </div>
    <div class="row">
        <span class="label">Date</span>
        <span class="value">:{{ $dateText }} Time {{ $timeText }}</span>
    </div>
    <div class="row">
        <span class="label">Terminal:</span>
        <span class="value">01</span>
    </div>
    <div class="row">
        <span class="label">Table # :</span>
        <span class="value">{{ $tableText }}</span>
    </div>
    <div class="row">
        <span class="label">Cashier :</span>
        <span class="value">{{ $order->waiter?->name ?: 'Cashier User' }}</span>
    </div>

    <div class="separator"></div>

    <div class="row items-header">
        <span>Item</span>
        <span>Qty&nbsp;&nbsp;&nbsp;Amount</span>
    </div>

    <div class="thin-separator"></div>

    @foreach($activeItems as $index => $item)
        @php
            $vatApplicable = $item->item?->vat_available ?? true;
            $ssclApplicable = $item->item?->sscl_available ?? true;
            $itemTax = $taxService->calculateItemTax(
                (float) $item->unit_price,
                (int) $item->quantity,
                $vatApplicable,
                $ssclApplicable
            );
            $baseUnitPrice = $item->quantity > 0
                ? $itemTax['base_amount'] / $item->quantity
                : 0;
        @endphp

        <div class="item-name">{{ $index + 1 }}. {{ $item->item_display_name ?: ($item->item->name ?? 'Item') }}</div>
        <div class="item-subline">
            <span>{{ $item->quantity }}x @ Rs. {{ number_format($baseUnitPrice, 2) }}</span>
            <span>{{ number_format($itemTax['base_amount'], 2) }}</span>
        </div>

        @if($item->modifiers->count() > 0)
            @php
                $nonPortionModifiers = \App\Helpers\PrintHelper::filterPortionModifiers($item->modifiers);
            @endphp
            @foreach($nonPortionModifiers as $modifier)
                <div class="item-subline" style="margin-left: 12px; font-size: 10px;">
                    <span>+ {{ $modifier->modifier?->name ?: ($modifier->modifier_name ?: 'Modifier') }}</span>
                    <span>(+Rs. {{ number_format($modifier->price_adjustment, 2) }})</span>
                </div>
            @endforeach
        @endif
    @endforeach

    <div class="separator"></div>

    <div class="total-row">
        <span>Sub Total (Base)</span>
        <span>{{ number_format($orderTax['subtotal'], 2) }}</span>
    </div>
    @if($orderTax['sscl_amount'] > 0)
        <div class="total-row">
            <span>SSCL ({{ $taxService->getSsclRate() }}%)</span>
            <span>{{ number_format($orderTax['sscl_amount'], 2) }}</span>
        </div>
    @endif
    @if($orderTax['vat_amount'] > 0)
        <div class="total-row">
            <span>VAT ({{ $taxService->getVatRate() }}%)</span>
            <span>{{ number_format($orderTax['vat_amount'], 2) }}</span>
        </div>
    @endif

    <div class="total-row grand-total">
        <span>Total</span>
        <span>{{ number_format($order->total_amount, 2) }}</span>
    </div>

    @if($order->payment)
        <div style="margin-top: 8px;">
            <div class="total-row">
                <span>Payment Method</span>
                <span>{{ strtoupper($order->payment->payment_method) }}</span>
            </div>
            @if($cashAmount > 0)
                <div class="total-row">
                    <span>Cash</span>
                    <span>{{ number_format($cashAmount, 2) }}</span>
                </div>
            @endif
            @if($cardAmount > 0)
                <div class="total-row">
                    <span>Card</span>
                    <span>{{ number_format($cardAmount, 2) }}</span>
                </div>
            @endif
            @if($creditAmount > 0)
                <div class="total-row">
                    <span>Credit</span>
                    <span>{{ number_format($creditAmount, 2) }}</span>
                </div>
            @endif
            @if($order->payment->change_amount > 0)
                <div class="total-row">
                    <span>Change</span>
                    <span>{{ number_format($order->payment->change_amount, 2) }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="text-center thanks">THANK YOU, COME AGAIN.</div>
    <div class="separator"></div>
    <div class="text-center small-footer">Software By Jayawardena Group</div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Receipt
        </button>
        <button onclick="window.close()"
            style="padding: 10px 24px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>

</html>
