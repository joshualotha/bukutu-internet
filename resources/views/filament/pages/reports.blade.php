@php
    $currency = config('pesapal.currency', 'TZS');
@endphp

<x-filament-panels::page>
    <x-filament-panels::form wire:submit="applyFilter">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-funnel">
                Apply Filter
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Summary Cards -->
    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Orders</div>
            <div class="text-2xl font-bold tracking-tight">{{ number_format($summary['total_orders'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</div>
            <div class="text-2xl font-bold tracking-tight text-success-600">{{ number_format($summary['total_revenue'] ?? 0, 0) }} {{ $currency }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm text-gray-500 dark:text-gray-400">Paid Orders</div>
            <div class="text-2xl font-bold tracking-tight text-primary-600">{{ number_format($summary['paid_orders'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm text-gray-500 dark:text-gray-400">Failed Orders</div>
            <div class="text-2xl font-bold tracking-tight text-danger-600">{{ number_format($summary['failed_orders'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Orders</div>
            <div class="text-2xl font-bold tracking-tight text-warning-600">{{ number_format($summary['pending_orders'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Customer Retention -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-lg font-semibold mb-4">Customer Retention</h3>
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <div class="text-sm text-gray-500">Total Customers</div>
                <div class="text-xl font-bold">{{ number_format($customerRetention['total_customers'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Returning</div>
                <div class="text-xl font-bold text-success-600">{{ number_format($customerRetention['returning_customers'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">One-Time</div>
                <div class="text-xl font-bold text-warning-600">{{ number_format($customerRetention['one_time_customers'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Retention Rate</div>
                <div class="text-xl font-bold text-primary-600">{{ number_format($customerRetention['retention_rate'] ?? 0, 1) }}%</div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <!-- Popular Packages -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-lg font-semibold mb-4">Popular Packages (Last 30 Days)</h3>
            @if(count($popularPackages) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-left font-medium text-gray-500">Package</th>
                            <th class="py-2 text-right font-medium text-gray-500">Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($popularPackages as $package)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $package['name'] ?? $package->name ?? 'N/A' }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format($package['total_orders'] ?? $package->total_orders ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500 text-sm">No data available</p>
            @endif
        </div>

        <!-- Device Usage -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-lg font-semibold mb-4">Device Usage (Last 30 Days)</h3>
            @if(count($deviceUsage) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-left font-medium text-gray-500">Device</th>
                            <th class="py-2 text-right font-medium text-gray-500">Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deviceUsage as $device)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $device['device_name'] ?? $device->device_name ?? 'Unknown' }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format($device['count'] ?? $device->count ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500 text-sm">No device data available</p>
            @endif
        </div>
    </div>

    <!-- Failed Payments -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-lg font-semibold mb-4">Recent Failed Payments</h3>
        @if(count($failedPayments) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-left font-medium text-gray-500">ID</th>
                            <th class="py-2 text-left font-medium text-gray-500">Customer</th>
                            <th class="py-2 text-right font-medium text-gray-500">Amount</th>
                            <th class="py-2 text-left font-medium text-gray-500">Status</th>
                            <th class="py-2 text-left font-medium text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedPayments as $payment)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $payment['id'] ?? $payment->id ?? 'N/A' }}</td>
                                <td class="py-2">{{ $payment->order->customer->full_name ?? $payment['order']['customer']['full_name'] ?? 'N/A' }}</td>
                                <td class="py-2 text-right">{{ number_format($payment['amount'] ?? $payment->amount ?? 0, 0) }} {{ $currency }}</td>
                                <td class="py-2">
                                    <x-filament::badge :color="$payment->status?->value === 'failed' ? 'danger' : 'warning'">
                                        {{ $payment->status?->value ?? $payment['status'] ?? 'N/A' }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2">{{ ($payment['created_at'] ?? $payment->created_at) ? \Carbon\Carbon::parse($payment['created_at'] ?? $payment->created_at)->format('Y-m-d H:i') : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm">No failed payments in this period</p>
        @endif
    </div>
</x-filament-panels::page>
