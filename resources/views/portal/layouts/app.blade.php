<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name')) - {{ __('portal.brand') }}</title>

    @vite('resources/css/app.css')
    @stack('styles')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div x-data="{ showLangMenu: false }" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        B
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ config('app.name') }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('portal.tagline') }}</p>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div class="relative">
                    <button @click="showLangMenu = !showLangMenu" class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ app()->getLocale() === 'sw' ? __('portal.swahili') : __('portal.english') }}</span>
                    </button>

                    <div x-show="showLangMenu" @click.outside="showLangMenu = false" class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg border dark:border-gray-600 z-50">
                        @foreach(['en' => 'English', 'sw' => 'Kiswahili'] as $code => $name)
                            <a href="{{ route('portal.lang', $code) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 {{ app()->getLocale() === $code ? 'font-bold' : '' }}">
                                {{ $name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t dark:border-gray-700 mt-auto">
            <div class="max-w-4xl mx-auto px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('portal.tagline') }}</p>
                <p class="mt-1">
                    <a href="{{ route('portal.terms') }}" class="underline hover:text-gray-700 dark:hover:text-gray-200">{{ __('portal.terms_title') }}</a>
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
