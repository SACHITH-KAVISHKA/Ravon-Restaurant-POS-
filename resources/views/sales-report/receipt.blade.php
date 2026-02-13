<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .receipt {
            background: white;
            color: black;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            /* Fixed font size */
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 10px;
            /* Fixed font size */
            margin: 2px 0;
        }

        .section {
            margin: 8px 0;
            padding: 8px 0;
            border-bottom: 1px dashed #000;
            page-break-inside: avoid;
            /* Prevent section from breaking across pages */
        }

        .section:last-child {
            border-bottom: 2px dashed #000;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
            /* Fixed font size */
        }

        .info-label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            font-weight: bold;
            font-size: 10px;
            /* Fixed font size */
        }

        table td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 10px;
            /* Fixed font size */
        }

        .item-name {
            max-width: 120px;
            word-wrap: break-word;
        }

        .item-modifier {
            font-size: 9px;
            /* Fixed font size */
            margin-left: 8px;
            color: #444;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 8px;
        }

        .totals .row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 10px;
            /* Fixed font size */
        }

        .totals .row.total {
            font-size: 12px;
            /* Fixed font size */
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 8px;
        }

        .payment-info {
            margin-top: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 12px;
            font-size: 10px;
            /* Fixed font size */
        }

        .footer p {
            margin: 4px 0;
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

            /* Ensure sections don't break awkwardly */
            .section {
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
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1>RAVON RESTAURANT</h1>
            <p>Point of Sale Receipt</p>
            <p>Thank You For Your Business!</p>
        </div>

        <!-- Order Information -->
        <div class="section">
            <div class="info-row">
                <span class="info-label">Order #:</span>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment #:</span>
                <span>{{ $order->payment ? $order->payment->payment_number : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span>{{ $order->completed_at ? $order->completed_at->format('M d, Y H:i') : now()->format('M d, Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Type:</span>
                <span>{{ $order->order_type === 'dine_in' ? 'Dine In' : ($order->order_type === 'takeaway' ? 'Take Away' : ($order->order_type === 'pickme' ? 'PickMe Food' : ($order->order_type === 'uber_eats' ? 'Uber Eats' : ucwords(str_replace('_', ' ', $order->order_type))))) }}</span>
            </div>
            @if($order->order_type === 'pickme' && $order->pickme_ref_number)
                <div class="info-row">
                    <span class="info-label">PickMe Ref:</span>
                    <span>{{ $order->pickme_ref_number }}</span>
                </div>
            @endif
            @if($order->waiter)
                <div class="info-row">
                    <span class="info-label">Waiter:</span>
                    <span>{{ $order->waiter->name }}</span>
                </div>
            @endif
            @if($order->customer_name)
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span>{{ $order->customer_name }}</span>
                </div>
            @endif
            @if($order->table)
                <div class="info-row">
                    <span class="info-label">Table:</span>
                    <span>{{ $order->table->table_number }}</span>
                </div>
            @endif
        </div>

        <!-- Order Items -->
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- IMPORTANT: Loop through ALL items without any limit - this ensures complete receipts --}}
                    @foreach($order->orderItems->where('status', '!=', 'deleted') as $item)
                        <tr>
                            <td class="item-name">
                                {{ $item->item_display_name }}
                                @if($item->modifiers->count() > 0)
                                    @php
                                        $nonPortionModifiers = \App\Helpers\PrintHelper::filterPortionModifiers($item->modifiers);
                                    @endphp
                                    @foreach($nonPortionModifiers as $modifier)
                                        <div class="item-modifier">
                                            + {{ $modifier->modifier_name }} ({{ number_format($modifier->price_adjustment, 2) }})
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="section">
            <div class="totals">
                <div class="row">
                    <span>Subtotal:</span>
                    <span>LKR {{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="row">
                        <span>Discount ({{ $order->discount_type == 'percentage' ? '%' : 'Fixed' }}):</span>
                        <span>- LKR {{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if($order->service_charge > 0)
                    <div class="row">
                        <span>Service Charge:</span>
                        <span>LKR {{ number_format($order->service_charge, 2) }}</span>
                    </div>
                @endif
                @if($order->tax_amount > 0)
                    <div class="row">
                        <span>Tax:</span>
                        <span>LKR {{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($order->delivery_fee > 0)
                    <div class="row">
                        <span>Delivery Fee:</span>
                        <span>LKR {{ number_format($order->delivery_fee, 2) }}</span>
                    </div>
                @endif
                <div class="row total">
                    <span>TOTAL:</span>
                    <span>LKR {{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        @if($order->payment)
            <div class="section">
                <div class="payment-info">
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span>{{ strtoupper($order->payment->payment_method) }}</span>
                    </div>
                    @if($cashAmount > 0)
                        <div class="info-row">
                            <span>Cash Payment:</span>
                            <span>LKR {{ number_format($cashAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($cardAmount > 0)
                        <div class="info-row">
                            <span>Card Payment:</span>
                            <span>LKR {{ number_format($cardAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($creditAmount > 0)
                        <div class="info-row">
                            <span>Credit Payment:</span>
                            <span>LKR {{ number_format($creditAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($order->payment->change_amount > 0)
                        <div class="info-row">
                            <span class="info-label">Change:</span>
                            <span>LKR {{ number_format($order->payment->change_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</p>
            <p><strong>Thank you for dining with us!</strong></p>
            <p>Please visit us again</p>
            <p>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</p>
            @if($order->special_instructions)
                <p style="margin-top: 10px; font-size: 10px;">
                    <strong>Special Instructions:</strong><br>
                    {{ $order->special_instructions }}
                </p>
            @endif
        </div>
    </div>

    <!-- Print Button (Hidden on Print) -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 30px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            Print Receipt
        </button>
        <button onclick="window.close()"
            style="padding: 10px 30px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            Close
        </button>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>

</html>