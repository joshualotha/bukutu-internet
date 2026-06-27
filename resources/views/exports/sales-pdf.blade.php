<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; margin-bottom: 20px; color: #1e40af; }
        h2 { color: #1e40af; margin: 20px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .summary { margin-bottom: 20px; }
        .summary-item { display: inline-block; margin-right: 30px; padding: 10px; background: #f3f4f6; border-radius: 5px; }
        .footer { text-align: center; color: #6b7280; font-size: 10px; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>Sales Report</h1>
    <p style="text-align: center;">Period: {{ $startDate }} to {{ $endDate }}</p>

    <div class="summary">
        <div class="summary-item">
            <strong>Total Orders:</strong> {{ $totalOrders }}
        </div>
        <div class="summary-item">
            <strong>Total Revenue:</strong> {{ number_format($totalRevenue, 0) }} TZS
        </div>
        <div class="summary-item">
            <strong>Paid Orders:</strong> {{ $paidOrders }}
        </div>
        <div class="summary-item">
            <strong>Failed Orders:</strong> {{ $failedOrders }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Package</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $order->order_reference }}</td>
                <td>{{ $order->customer?->full_name ?? 'N/A' }}</td>
                <td>{{ $order->package?->name ?? 'N/A' }}</td>
                <td>{{ number_format((float) $order->amount, 0) }}</td>
                <td>{{ $order->status?->value ?? 'N/A' }}</td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No orders found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} — Buku Tu Internet
    </div>
</body>
</html>
