<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-center mb-2">License Checker</h1>
            <p class="text-center text-blue-100">Verify if a website has a valid license</p>
        </div>

        <!-- Form -->
        <div class="p-8">
            <form wire:submit.prevent="checkLicense" class="space-y-6">
                <div>
                    <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Website Domain
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="domain"
                            wire:model="domain"
                            placeholder="example.com or https://example.com"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                            :disabled="checking"
                        >
                    </div>
                    @error('domain')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Enter the domain you want to check (with or without http/https)
                    </p>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="checking"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="checkLicense">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="checkLicense">
                            <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="checkLicense">Check License</span>
                        <span wire:loading wire:target="checkLicense">Checking...</span>
                    </button>

                    @if($result)
                        <button
                            type="button"
                            wire:click="resetForm"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Reset
                        </button>
                    @endif
                </div>
            </form>

            <!-- Results -->
            @if($result)
                <div class="mt-8 animate-fadeIn">
                    @if($result['has_license'] && $result['is_valid'] ?? false)
                        <!-- Valid License -->
                        <div class="border-2 border-green-500 rounded-lg p-6 bg-green-50 dark:bg-green-900/20">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">
                                        ✓ Valid License Found
                                    </h3>
                                    <p class="text-green-700 dark:text-green-300 mb-4">
                                        {{ $result['message'] }}
                                    </p>
                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">License Type</p>
                                            <p class="font-semibold text-gray-900 dark:text-white capitalize">
                                                {{ $result['license_type'] ?? 'N/A' }}
                                            </p>
                                        </div>
                                        @if($result['expires_at'] ?? null)
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Expires</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    {{ \Carbon\Carbon::parse($result['expires_at'])->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Days Remaining</p>
                                                <p class="font-semibold {{ ($result['days_until_expiration'] ?? 0) <= 7 ? 'text-orange-600' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $result['days_until_expiration'] ?? 'N/A' }} days
                                                </p>
                                            </div>
                                        @else
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Validity</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    Lifetime
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                    @if($result['is_expiring_soon'] ?? false)
                                        <div class="mt-4 bg-orange-100 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-700 rounded-lg p-3">
                                            <p class="text-sm text-orange-800 dark:text-orange-200 flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span class="font-medium">This license is expiring soon!</span>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif($result['has_license'] && !($result['is_valid'] ?? true))
                        <!-- Invalid License -->
                        <div class="border-2 border-orange-500 rounded-lg p-6 bg-orange-50 dark:bg-orange-900/20">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-orange-800 dark:text-orange-200 mb-2">
                                        ⚠ License Found (Invalid)
                                    </h3>
                                    <p class="text-orange-700 dark:text-orange-300">
                                        {{ $result['message'] }}
                                    </p>
                                    @if($result['status'] ?? null)
                                        <div class="mt-3 inline-block px-3 py-1 bg-orange-200 dark:bg-orange-800 rounded-full">
                                            <span class="text-sm font-medium text-orange-900 dark:text-orange-100">
                                                Status: {{ ucfirst($result['status']) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- No License -->
                        <div class="border-2 border-red-500 rounded-lg p-6 bg-red-50 dark:bg-red-900/20">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-red-800 dark:text-red-200 mb-2">
                                        ✗ No License Found
                                    </h3>
                                    <p class="text-red-700 dark:text-red-300">
                                        {{ $result['message'] }}
                                    </p>
                                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">
                                        This website does not have an active license for our platform.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Info Section -->
        <div class="bg-gray-50 dark:bg-gray-900 px-8 py-6 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">How it works:</h3>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Enter any website domain to check if it has a valid license</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>We'll verify the license status and expiration date</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Results are instant and accurate</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
</style>
