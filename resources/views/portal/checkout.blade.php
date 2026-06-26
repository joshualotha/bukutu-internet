@extends('portal.layouts.app')

@section('title', __('portal.checkout'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-8" x-data="checkoutForm()">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('portal.checkout') }}</h2>
        <p class="text-gray-600 dark:text-gray-300 mt-2">{{ __('portal.order_summary') }}</p>
    </div>

    <!-- Package Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('portal.order_summary') }}</h3>
        <div class="flex justify-between items-start">
            <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ $package->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $package->duration_minutes }} {{ __('portal.minutes') }}
                    @if($package->download_speed) | {{ __('portal.download_speed') }}: {{ $package->download_speed }} @endif
                </p>
            </div>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($package->price, 0) }} UGX</p>
        </div>
    </div>

    <!-- Checkout Form -->
    <form @submit.prevent="submitOrder" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <!-- Phone Number -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('portal.phone_number') }} <span class="text-red-500">*</span>
            </label>
            <input type="tel" id="phone" x-model="form.phone_number" required
                   placeholder="{{ __('portal.phone_number_placeholder') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
        </div>

        <!-- Full Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('portal.full_name') }}
            </label>
            <input type="text" id="name" x-model="form.full_name"
                   placeholder="{{ __('portal.full_name_placeholder') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
        </div>

        <!-- Terms -->
        <div class="flex items-start">
            <input type="checkbox" id="terms" x-model="form.agree_terms" required
                   class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="terms" class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                {{ __('portal.agree_terms') }}
                <a href="{{ route('portal.terms') }}" target="_blank" class="text-blue-600 hover:text-blue-700 underline">{{ __('portal.terms_title') }}</a>
            </label>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-text="error" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm"></div>

        <!-- Submit Button -->
        <button type="submit" :disabled="loading || !form.agree_terms"
                class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors flex items-center justify-center">
            <svg x-show="loading" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span x-text="loading ? '{{ __('portal.processing_payment') }}' : '{{ __('portal.pay_now') }}'"></span>
        </button>
    </form>

    <div class="text-center mt-6">
        <a href="{{ route('portal.packages', request()->query()) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">
            {{ __('portal.back') }}
        </a>
    </div>
</div>

@push('scripts')
<script>
function checkoutForm() {
    return {
        form: {
            phone_number: '',
            full_name: '',
            agree_terms: false,
        },
        loading: false,
        error: '',

        submitOrder() {
            this.loading = true;
            this.error = '';

            const params = new URLSearchParams(window.location.search);
            const mac = params.get('mac') || '';
            const ip = params.get('ip') || '';
            const routerId = params.get('router_id') || '';

            fetch('/api/portal/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    mac_address: mac,
                    package_id: '{{ $package->id }}',
                    phone_number: this.form.phone_number,
                    full_name: this.form.full_name,
                    router_id: routerId || null,
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.redirect_url) {
                    // Redirect to Pesapal
                    window.location.href = data.data.redirect_url;
                } else if (data.success && data.data.order) {
                    // If no redirect URL but order created, poll for status
                    window.location.href = '{{ route('portal.processing', ['reference' => '__REF__']) }}'.replace('__REF__', data.data.order.order_reference);
                } else {
                    this.error = data.message || '{{ __('portal.error_occurred') }}';
                }
            })
            .catch(err => {
                this.error = '{{ __('portal.error_occurred') }}';
                console.error(err);
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }
}
</script>
@endpush
@endsection
