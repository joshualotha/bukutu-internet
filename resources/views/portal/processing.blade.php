@extends('portal.layouts.app')

@section('title', __('portal.processing_payment'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-16 text-center" x-data="paymentPolling()" x-init="startPolling()">
    <!-- Spinner -->
    <div class="mb-8">
        <div class="w-20 h-20 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('portal.processing_payment') }}</h2>
    <p class="text-gray-600 dark:text-gray-300 mb-8">{{ __('portal.waiting_confirmation') }}</p>

    <!-- Order Reference -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6 inline-block">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('portal.order_reference') }}</p>
        <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $reference }}</p>
    </div>

    <!-- Time Elapsed -->
    <p class="text-sm text-gray-400 dark:text-gray-500">
        <span x-text="elapsed"></span>
    </p>

    <!-- Error State -->
    <div x-show="error" x-cloak>
        <p class="text-red-600 dark:text-red-400 mb-4" x-text="error"></p>
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            {{ __('portal.try_again') }}
        </a>
    </div>

    <!-- Success State (hidden, redirects) -->
    <div x-show="success" x-cloak>
        <p class="text-green-600 dark:text-green-400 font-semibold">{{ __('portal.payment_successful') }}</p>
    </div>
</div>

@push('scripts')
<script>
function paymentPolling() {
    return {
        reference: '{{ $reference }}',
        elapsed: '0s',
        attempts: 0,
        maxAttempts: 60,
        error: '',
        success: false,
        interval: null,

        startPolling() {
            // Start elapsed timer
            const startTime = Date.now();
            this.elapsedTimer = setInterval(() => {
                const seconds = Math.floor((Date.now() - startTime) / 1000);
                this.elapsed = seconds + 's';
            }, 1000);

            // Poll for order status
            this.poll();
        },

        poll() {
            if (this.attempts >= this.maxAttempts) {
                this.error = '{{ __('portal.payment_failed_message') }}';
                clearInterval(this.interval);
                clearInterval(this.elapsedTimer);
                return;
            }

            this.attempts++;

            fetch('/api/portal/orders/' + this.reference, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const status = data.data.status;

                    if (status === 'paid') {
                        this.success = true;
                        clearInterval(this.interval);
                        clearInterval(this.elapsedTimer);
                        // Redirect to success page
                        const params = new URLSearchParams(window.location.search);
                        setTimeout(() => {
                            window.location.href = '{{ route('portal.success', ['reference' => '__REF__']) }}'.replace('__REF__', this.reference);
                        }, 1000);
                        return;
                    } else if (status === 'failed' || status === 'expired') {
                        this.error = '{{ __('portal.payment_failed_message') }}';
                        clearInterval(this.interval);
                        clearInterval(this.elapsedTimer);
                        return;
                    }
                }

                // Still pending, poll again in 3 seconds
                setTimeout(() => this.poll(), 3000);
            })
            .catch(err => {
                setTimeout(() => this.poll(), 3000);
            });
        }
    }
}
</script>
@endpush
@endsection
