<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Amount') }}</p>
            <p class="text-lg font-semibold">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
            <x-filament::badge
                :color="match($payment->status?->value) {
                    'paid' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    'expired' => 'gray',
                    'refunded' => 'info',
                    default => 'gray',
                }"
            >
                {{ $payment->status?->value ?? '—' }}
            </x-filament::badge>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Payment Method') }}</p>
            <p>{{ $payment->payment_method ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Provider') }}</p>
            <p>{{ $payment->provider ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Provider Reference') }}</p>
            <p class="font-mono text-sm break-all">{{ $payment->provider_reference ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Tracking ID') }}</p>
            <p class="font-mono text-sm break-all">{{ $payment->provider_tracking_id ?? '—' }}</p>
        </div>
        @if($payment->phone_number)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Phone Number') }}</p>
            <p>{{ $payment->phone_number }}</p>
        </div>
        @endif
        @if($payment->confirmation_code)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Confirmation Code') }}</p>
            <p class="font-mono">{{ $payment->confirmation_code }}</p>
        </div>
        @endif
        @if($payment->payment_time)
        <div class="col-span-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Payment Time') }}</p>
            <p>{{ $payment->payment_time->format('M d, Y H:i:s') }}</p>
        </div>
        @endif
    </div>

    @if($payment->response_payload)
    <div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Response Payload') }}</p>
        <pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg text-xs overflow-x-auto max-h-48"><code>{{ json_encode($payment->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
    </div>
    @endif
</div>
