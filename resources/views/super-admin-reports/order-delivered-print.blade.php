<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 24px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 24px;
            color: #5b21b6;
        }

        p {
            margin: 0 0 16px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #764ba2;
            color: #ffffff;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .badge-paid,
        .badge-unpaid,
        .badge-completed,
        .badge-incomplete {
            padding: 2px 8px;
            border-radius: 9999px;
        }

        .badge-paid,
        .badge-completed {
            color: #166534;
            background: #dcfce7;
        }

        .badge-unpaid {
            color: #374151;
            background: #f3f4f6;
        }

        .badge-incomplete {
            color: #9a3412;
            background: #ffedd5;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>Order Delivered Report</h1>
    <p>Filtered results from {{ $filters['start_date'] }} to {{ $filters['end_date'] }}</p>

    <table>
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Table ID</th>
                <th>Date</th>
                <th>Order Type</th>
                <th>Paid or Not</th>
                <th class="right">Sub Total</th>
                <th>Order Created Time</th>
                <th>Order Closed Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @php $paid = ($order->payment?->payment_status === 'completed' || $order->is_paid); @endphp
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->table?->table_number ?? 'N/A' }}</td>
                    <td>{{ $order->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</td>
                    <td>{!! $paid ? '<span class="badge-paid">Paid</span>' : '<span class="badge-unpaid">Unpaid</span>' !!}</td>
                    <td class="right">LKR {{ number_format($order->subtotal ?? 0, 2) }}</td>
                    <td>{{ $order->created_at?->format('H:i:s') ?? 'N/A' }}</td>
                    <td>{{ $order->updated_at?->format('H:i:s') ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">No Data Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
