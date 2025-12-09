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

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            max-width: 80mm;
            margin: 0 auto;
        }

        .receipt {
            background: white;
            color: black;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            margin: 2px 0;
        }

        .section {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px dashed #000;
        }

        .section:last-child {
            border-bottom: 2px dashed #000;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .info-label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            font-weight: bold;
        }

        table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .item-name {
            max-width: 150px;
            word-wrap: break-word;
        }

        .item-modifier {
            font-size: 10px;
            margin-left: 10px;
            color: #555;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 10px;
        }

        .totals .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .totals .row.total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }

        .payment-info {
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
        }

        .footer p {
            margin: 5px 0;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
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
                <span>{{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</span>
            </div>
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
                    @foreach($order->orderItems as $item)
                    <tr>
                        <td class="item-name">
                            {{ $item->item_display_name }}
                            @if($item->modifiers->count() > 0)
                                @foreach($item->modifiers as $modifier)
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
        <button onclick="window.print()" style="padding: 10px 30px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
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
