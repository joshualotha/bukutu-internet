@extends('portal.layouts.app')

@section('title', __('portal.payment_failed'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <!-- Error Icon -->
    <div class="mb-8">
        <div class="w-24 h-24 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('portal.payment_failed') }}</h2>
    <p class="text-gray-600 dark:text-gray-300 mb-8">{{ __('portal.payment_failed_message') }}</p>

    @if(isset($message))
    <p class="text-sm text-red-600 dark:text-red-400 mb-8">{{ $message }}</p>
    @endif

    <div class="space-y-3">
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="block w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
            {{ __('portal.try_again') }}
        </a>
        <a href="{{ route('portal.landing', request()->query()) }}"
           class="block w-full px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors">
            {{ __('portal.contact_support') }}
        </a>
    </div>
</div>
@endsection
