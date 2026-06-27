<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function title(): string
    {
        return 'Customer Report';
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'MAC Address',
            'IP Address',
            'Device',
            'Total Orders',
            'Total Spent',
            'Registered At',
        ];
    }

    public function collection()
    {
        return Customer::withCount('orders')
            ->withSum('orders', 'amount')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Customer $customer) => [
                $customer->full_name ?? 'N/A',
                $customer->phone_number ?? 'N/A',
                $customer->email ?? 'N/A',
                $customer->mac_address,
                $customer->ip_address ?? 'N/A',
                $customer->device_name ?? 'N/A',
                $customer->orders_count ?? 0,
                (float) ($customer->orders_sum_amount ?? 0),
                $customer->created_at->toDateTimeString(),
            ]);
    }
}
