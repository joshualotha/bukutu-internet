@extends('portal.layouts.app')

@section('title', __('portal.my_session'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('portal.my_session') }}</h2>
    </div>

    @if(isset($session) && $session)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="text-center mb-6">
            <!-- Status indicator -->
            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $session->isActive() ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                <span class="w-2 h-2 rounded-full mr-2 {{ $session->isActive() ? 'bg-green-500' : 'bg-red-500' }}"></span>
                {{ $session->isActive() ? __('portal.active') : ($session->status->value === 'suspended' ? __('portal.suspended') : __('portal.expired')) }}
            </div>
        </div>

        @if($session->package)
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.package') }}</span>
                <span class="font-medium">{{ $session->package->name }}</span>
            </div>
            @if($session->package->download_speed)
            <div class="flex justify-between py-2 border-b dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.download_speed') }}</span>
                <span class="font-medium">{{ $session->package->download_speed }}</span>
            </div>
            @endif
            <div class="flex justify-between py-2 border-b dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.time_remaining') }}</span>
                <span class="font-medium text-green-600 dark:text-green-400" x-data="timer()" x-init="start('{{ $session->expiry_time }}')">
                    <span x-text="display"></span>
                </span>
            </div>
            @if($session->start_time)
            <div class="flex justify-between py-2">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.created_at') }}</span>
                <span class="font-medium">{{ $session->start_time->format('d M Y H:i') }}</span>
            </div>
            @endif
            @if($session->expiry_time)
            <div class="flex justify-between py-2">
                <span class="text-gray-500 dark:text-gray-400">{{ __('portal.expired_at') }}</span>
                <span class="font-medium">{{ $session->expiry_time->format('d M Y H:i') }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>

    <div class="space-y-3">
        @if($session->isActive())
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="block w-full text-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
            {{ __('portal.buy_more') }}
        </a>
        @else
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="block w-full text-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
            {{ __('portal.connect') }}
        </a>
        @endif
        <a href="http://google.com" target="_blank"
           class="block w-full text-center px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors">
            {{ __('portal.start_browsing') }}
        </a>
    </div>
    @else
    <div class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('portal.session_not_found') }}</p>
        <a href="{{ route('portal.packages', request()->query()) }}"
           class="inline-flex px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
            {{ __('portal.connect') }}
        </a>
    </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('portal.landing', request()->query()) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 underline">
            {{ __('portal.back') }}
        </a>
    </div>
</div>

@push('scripts')
<script>
function timer() {
    return {
        display: '',
        start(expiryTime) {
            const expiry = new Date(expiryTime).getTime();
            setInterval(() => {
                const now = new Date().getTime();
                const diff = Math.max(0, Math.floor((expiry - now) / 1000));
                const h = Math.floor(diff / 3600);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;
                this.display = h + 'h ' + m + 'm ' + s + 's';
            }, 1000);
        }
    }
}
</script>
@endpush
@endsection
