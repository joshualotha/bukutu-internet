@extends('portal.layouts.app')

@section('title', __('portal.select_package'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('portal.select_package') }}</h2>
        <p class="text-gray-600 dark:text-gray-300 mt-2">{{ __('portal.tagline') }}</p>
    </div>

    <div id="packages" class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ loading: false }">
        @forelse($packages as $package)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $package->name }}</h3>
                @if($package->description)
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $package->description }}</p>
                @endif

                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-4">
                    {{ number_format($package->price, 0) }} TZS
                </div>

                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300 mb-6">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ $package->duration_minutes }} {{ __('portal.minutes') }}</span>
                    </li>
                    @if($package->download_speed)
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ __('portal.download_speed') }}: {{ $package->download_speed }}</span>
                    </li>
                    @endif
                    @if($package->upload_speed)
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ __('portal.upload_speed') }}: {{ $package->upload_speed }}</span>
                    </li>
                    @endif
                </ul>

                <a href="{{ route('portal.checkout', ['package_id' => $package->id] + request()->query()) }}"
                   class="block w-full text-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    {{ __('portal.buy_now') }}
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
            {{ __('portal.loading') }}
        </div>
        @endforelse
    </div>

    <div class="text-center mt-8">
        <a href="{{ route('portal.landing', request()->query()) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">
            {{ __('portal.back') }}
        </a>
    </div>
</div>
@endsection
