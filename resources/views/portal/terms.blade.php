@extends('portal.layouts.app')

@section('title', __('portal.terms_title'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('portal.terms_title') }}</h2>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 prose dark:prose-invert max-w-none text-sm text-gray-600 dark:text-gray-300 space-y-4">
        <p>{{ __('portal.terms_content') }}</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">1. Service Description</h3>
        <p>Buku Tu Internet provides prepaid wireless internet access services. By purchasing a package, you agree to use the service in compliance with all applicable laws and regulations.</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2. Payments & Refunds</h3>
        <p>All payments are processed through Pesapal. Service is activated immediately upon payment confirmation. No refunds will be issued for partially used time. If you experience technical issues, please contact support.</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">3. Fair Usage Policy</h3>
        <p>Internet access is provided on a best-effort basis. Speed may vary based on network conditions, signal strength, and number of concurrent users. We reserve the right to manage traffic to ensure fair distribution of bandwidth.</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">4. Prohibited Activities</h3>
        <p>You may not use this service for any illegal activities, including but not limited to: hacking, unauthorized access to systems, distribution of malicious software, copyright infringement, or any activity that disrupts network services.</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">5. Limitation of Liability</h3>
        <p>Buku Tu Internet shall not be liable for any indirect, incidental, or consequential damages arising from the use or inability to use the service. Our maximum liability is limited to the amount paid for the current service package.</p>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">6. Contact</h3>
        <p>For support, please contact us through the venue's management or send an email to support@bukutu.co.tz.</p>
    </div>

    <div class="text-center mt-6">
        <a href="javascript:history.back()" class="text-sm text-blue-600 hover:text-blue-700 underline">{{ __('portal.back') }}</a>
    </div>
</div>
@endsection
