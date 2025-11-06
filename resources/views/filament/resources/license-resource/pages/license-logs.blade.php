<x-filament-panels::page>
    <div class="space-y-6">
        {{-- License Information Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">License Key</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $record->license_key }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Domain</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $record->domain }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Checks</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($record->check_count) }}</p>
                </div>
            </div>
        </div>

        {{-- Logs Table --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
