{{-- Language Switcher Component --}}
@php
    $currentLocale = app()->getLocale();
    $availableLocales = [
        'ro' => ['name' => __('navigation.romanian'), 'flag' => '🇷🇴'],
        'en' => ['name' => __('navigation.english'), 'flag' => '🇬🇧'],
    ];
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open"
            @click.away="open = false"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        <span class="mr-2">{{ $availableLocales[$currentLocale]['flag'] }}</span>
        <span>{{ $availableLocales[$currentLocale]['name'] }}</span>
        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         role="menu">
        @foreach($availableLocales as $locale => $data)
            <a href="{{ route('locale.switch', $locale) }}"
               class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $currentLocale === $locale ? 'bg-gray-50 dark:bg-gray-900' : '' }}"
               role="menuitem">
                <span class="mr-3 text-2xl">{{ $data['flag'] }}</span>
                <span>{{ $data['name'] }}</span>
                @if($currentLocale === $locale)
                    <svg class="ml-auto h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
