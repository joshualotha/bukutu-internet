@extends('portal.layouts.app')

@section('title', __('portal.connect'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12 text-center">
    <!-- Hero Section -->
    <div class="mb-12">
        <div class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center text-white mx-auto mb-6">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"></path>
            </svg>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {{ __('portal.connect') }}
        </h2>
        <p class="text-lg text-gray-600 dark:text-gray-300 max-w-xl mx-auto">
            {{ __('portal.tagline') }} — {{ __('portal.select_package') }}
        </p>
    </div>

    <!-- CTA Button -->
    <a href="{{ route('portal.packages', request()->query()) }}"
       class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 text-lg">
        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        {{ __('portal.get_started', __('portal.connect')) }}
    </a>

    <!-- Features -->
    <div class="grid md:grid-cols-3 gap-6 mt-16 text-left">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('portal.select_package') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">Choose from our range of affordable internet packages.</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('portal.pay_now') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">Secure payment via mobile money or card with Pesapal.</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('portal.you_are_connected') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">Get instant internet access after payment confirmation.</p>
        </div>
    </div>
</div>
@endsection
