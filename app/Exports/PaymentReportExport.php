<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PaymentReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function title(): string
    {
        return 'Payment Report';
    }

    public function headings(): array
    {
        return [
            'Payment ID',
            'Order Reference',
            'Customer',
            'Amount',
            'Currency',
            'Provider',
            'Method',
            'Status',
            'Confirmation Code',
            'Payment Time',
        ];
    }

    public function collection()
    {
        return Payment::with('order.customer')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Payment $payment) => [
                $payment->id,
                $payment->order?->order_reference ?? 'N/A',
                $payment->order?->customer?->full_name ?? 'N/A',
                (float) $payment->amount,
                $payment->currency ?? 'UGX',
                $payment->provider ?? 'N/A',
                $payment->payment_method ?? 'N/A',
                $payment->status?->value ?? 'N/A',
                $payment->confirmation_code ?? 'N/A',
                $payment->payment_time?->toDateTimeString() ?? 'N/A',
            ]);
    }
}
