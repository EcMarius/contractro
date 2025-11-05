<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
    <!-- Used to add dark mode right away, adding here prevents any flicker -->
    <script>
        if (typeof(Storage) !== "undefined") {
            if(localStorage.getItem('theme') && localStorage.getItem('theme') == 'dark'){
                document.documentElement.classList.add('dark');
            }
        }
        document.addEventListener("livewire:navigated", () => {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
</head>
<body x-data="{ mobileMenuOpen: false }" class="flex flex-col min-h-screen bg-gray-50 dark:bg-zinc-900 @if(config('wave.dev_bar')){{ 'pb-10' }}@endif">

    {{-- Top Navigation --}}
    <x-navigation />

    {{-- Main Content --}}
    <main class="flex-1">
        <div class="w-full h-full">
            {{ $slot }}
        </div>
    </main>

    @livewire('notifications')

    {{-- Feedback Form --}}
    @livewire('contractro-feedback-form')

    <x-marketing.cookie-banner />

    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}


</body>
</html>

