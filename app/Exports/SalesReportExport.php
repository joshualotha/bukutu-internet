<?php

namespace App\Exports;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function title(): string
    {
        return 'Sales Report';
    }

    public function headings(): array
    {
        return [
            'Order Reference',
            'Customer Name',
            'Phone',
            'Package',
            'Amount',
            'Status',
            'Payment Method',
            'Paid At',
            'Created At',
        ];
    }

    public function collection()
    {
        return Order::with(['customer', 'package'])
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => [
                $order->order_reference,
                $order->customer?->full_name ?? 'N/A',
                $order->customer?->phone_number ?? 'N/A',
                $order->package?->name ?? 'N/A',
                (float) $order->amount,
                $order->status?->value ?? 'N/A',
                $order->payment_method ?? 'N/A',
                $order->paid_at?->toDateTimeString() ?? 'N/A',
                $order->created_at->toDateTimeString(),
            ]);
    }
}
