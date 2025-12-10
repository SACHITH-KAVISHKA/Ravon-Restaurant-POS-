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

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .info {
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .items {
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .item {
            margin-bottom: 8px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }

        .item-details {
            font-size: 10px;
            color: #666;
            margin-left: 10px;
        }

        .totals {
            margin-bottom: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .total-row.grand {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }

        .payment {
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
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
    <div class="header">
        <h1>RAVON RESTAURANT</h1>
        <div>Tax Invoice</div>
        <div>{{ now()->format('d/m/Y h:i A') }}</div>
    </div>

    <div class="info">
        <div class="info-row">
            <span>Bill No:</span>
            <span>{{ $order->order_number }}</span>
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
        @if($order->customer_name)
        <div class="info-row">
            <span>Customer:</span>
            <span>{{ $order->customer_name }}</span>
        </div>
        @endif
    </div>

    <div class="items">
        @foreach($order->orderItems as $item)
        <div class="item">
            <div class="item-header">
                <span>{{ $item->quantity }} x {{ $item->item->name }}</span>
                <span>{{ number_format($item->subtotal, 2) }}</span>
            </div>
            <div class="item-details">
                @ Rs. {{ number_format($item->unit_price, 2) }} each
            </div>
            @if($item->modifiers->count() > 0)
            @php
                $nonPortionModifiers = \App\Helpers\PrintHelper::filterPortionModifiers($item->modifiers);
            @endphp
            @foreach($nonPortionModifiers as $modifier)
            <div class="item-details">
                + {{ $modifier->modifier ? $modifier->modifier->name : 'Modifier' }} (Rs. {{ number_format($modifier->price_adjustment, 2) }})
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
            <span>Subtotal:</span>
            <span>Rs. {{ number_format($order->subtotal, 2) }}</span>
        </div>
        @if($order->discount_amount > 0)
        <div class="total-row">
            <span>Discount ({{ $order->discount_type === 'percentage' ? $order->discount_value.'%' : 'Fixed' }}):</span>
            <span>- Rs. {{ number_format($order->discount_amount, 2) }}</span>
        </div>
        @endif
        @if($order->tax_amount > 0)
        <div class="total-row">
            <span>Tax:</span>
            <span>Rs. {{ number_format($order->tax_amount, 2) }}</span>
        </div>
        @endif
        @if($order->service_charge > 0)
        <div class="total-row">
            <span>Service Charge:</span>
            <span>Rs. {{ number_format($order->service_charge, 2) }}</span>
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
        <p style="margin-top: 10px;">www.ravonrestaurant.com</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>