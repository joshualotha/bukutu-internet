<div class="space-y-4">
    @if(empty($data))
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p>{{ __('No payload data available.') }}</p>
        </div>
    @else
        <pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg text-sm overflow-x-auto max-h-96"><code>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
    @endif
</div>
