@extends('portal.layouts.app')

@section('title', __('portal.payment_successful'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-12 text-center">
    <!-- Success Icon -->
    <div class="mb-8">
        <div class="w-24 h-24 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('portal.payment_successful') }}</h2>
    <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">{{ __('portal.you_are_connected') }}</p>

    <!-- Session Details -->
    @if(isset($session) && $session)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8 text-left">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('portal.session_details') }}</h3>

        <div class="space-y-3 text-sm">
            @if($session->package)
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.package') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $session->package->name }}</span>
            </div>
            @endif
            @if($session->package && $session->package->download_speed)
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.download_speed') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $session->package->download_speed }}</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.expires_in') }}</span>
                <span class="font-medium text-green-600 dark:text-green-400" x-data="{ timer: null }" x-init="
                    const expiry = new Date('{{ $session->expiry_time }}').getTime();
                    setInterval(() => {
                        const now = new Date().getTime();
                        const diff = Math.max(0, Math.floor((expiry - now) / 1000));
                        const hours = Math.floor(diff / 3600);
                        const mins = Math.floor((diff % 3600) / 60);
                        const secs = diff % 60;
                        $el.textContent = hours + 'h ' + mins + 'm ' + secs + 's';
                    }, 1000);
                ">{{ $session->timeRemaining() }}s</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="space-y-3">
        <a href="http://google.com" target="_blank"
           class="block w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
            {{ __('portal.start_browsing') }}
        </a>
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="block w-full px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors">
            {{ __('portal.buy_more') }}
        </a>
    </div>
</div>
@endsection
