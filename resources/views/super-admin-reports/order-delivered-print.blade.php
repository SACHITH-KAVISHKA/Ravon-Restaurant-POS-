<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Preparation Performance Report</title>
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

        .center {
            text-align: center;
        }

        .badge-fast {
            padding: 2px 8px;
            border-radius: 9999px;
            color: #166534;
            background: #dcfce7;
        }

        .badge-normal {
            padding: 2px 8px;
            border-radius: 9999px;
            color: #854d0e;
            background: #fef9c3;
        }

        .badge-slow {
            padding: 2px 8px;
            border-radius: 9999px;
            color: #991b1b;
            background: #fee2e2;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>Item Preparation Performance Report</h1>
    <p>Filtered results from {{ $filters['start_date'] }} to {{ $filters['end_date'] }}</p>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th class="center">Times Ordered</th>
                <th class="center">Total Quantity</th>
                <th class="center">Avg Prep Time</th>
                <th class="center">Fastest Time</th>
                <th class="center">Slowest Time</th>
                <th class="center">Performance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $avgPrepTime = (int) ($item->avg_prep_time ?? 0);
                    if ($avgPrepTime < 10) {
                        $perfLabel = 'Fast';
                        $perfClass = 'badge-fast';
                    } elseif ($avgPrepTime <= 20) {
                        $perfLabel = 'Normal';
                        $perfClass = 'badge-normal';
                    } else {
                        $perfLabel = 'Slow';
                        $perfClass = 'badge-slow';
                    }
                @endphp
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td class="center">{{ number_format($item->times_ordered) }}</td>
                    <td class="center">{{ number_format($item->total_quantity) }}</td>
                    <td class="center">{{ $avgPrepTime }} min</td>
                    <td class="center">{{ (int) ($item->min_prep_time ?? 0) }} min</td>
                    <td class="center">{{ (int) ($item->max_prep_time ?? 0) }} min</td>
                    <td class="center"><span class="{{ $perfClass }}">{{ $perfLabel }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">No Data Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
