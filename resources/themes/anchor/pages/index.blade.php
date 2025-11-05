<?php
    use function Laravel\Folio\{name};
    name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => __('marketing.seo_title'),
        'description'   => __('marketing.seo_description'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>

    {{-- Hero Section --}}
    <section class="relative py-20 overflow-hidden bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 dark:text-white leading-tight">
                        {{ __('marketing.hero_title') }}
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">{{ __('marketing.hero_title_highlight') }}</span>
                    </h1>
                    <p class="mt-6 text-xl text-gray-600 dark:text-gray-300">
                        {{ __('marketing.hero_subtitle') }}
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 border border-transparent rounded-lg font-semibold text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition text-base">
                            {{ __('marketing.try_free') }}
                        </a>
                        <a href="#features"
                           class="inline-flex items-center justify-center px-8 py-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 transition text-base">
                            {{ __('marketing.see_features') }}
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        ✓ {{ __('marketing.no_card_required') }}  ✓ {{ __('marketing.setup_2_minutes') }}  ✓ {{ __('marketing.support_romanian') }}
                    </p>
                </div>
                <div class="relative flex items-center justify-center">
                    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
                    <lottie-player
                        src="https://lottie.host/f1e2cf8c-7ef1-4383-924c-fbbb669ad235/CTZ8B4nrvb.lottie"
                        background="transparent"
                        speed="1"
                        style="width: 100%; height: 500px;"
                        loop
                        autoplay>
                    </lottie-player>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white">{{ __('marketing.features_title') }}</h2>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">{{ __('marketing.features_subtitle') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $features = [
                        ['icon' => '📝', 'title' => __('marketing.feature_templates_title'), 'desc' => __('marketing.feature_templates_desc')],
                        ['icon' => '✍️', 'title' => __('marketing.feature_esignature_title'), 'desc' => __('marketing.feature_esignature_desc')],
                        ['icon' => '🏢', 'title' => __('marketing.feature_multicompany_title'), 'desc' => __('marketing.feature_multicompany_desc')],
                        ['icon' => '💰', 'title' => __('marketing.feature_invoicing_title'), 'desc' => __('marketing.feature_invoicing_desc')],
                        ['icon' => '📊', 'title' => __('marketing.feature_reports_title'), 'desc' => __('marketing.feature_reports_desc')],
                        ['icon' => '🔒', 'title' => __('marketing.feature_security_title'), 'desc' => __('marketing.feature_security_desc')],
                    ];
                @endphp
                @foreach($features as $feature)
                    <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-xl hover:shadow-lg transition">
                        <div class="text-4xl mb-4">{{ $feature['icon'] }}</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="py-20 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white">{{ __('marketing.how_it_works') }}</h2>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">{{ __('marketing.simple_steps') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                @php
                    $steps = [
                        ['num' => '1', 'title' => __('marketing.step_1_title'), 'desc' => __('marketing.step_1_desc')],
                        ['num' => '2', 'title' => __('marketing.step_2_title'), 'desc' => __('marketing.step_2_desc')],
                        ['num' => '3', 'title' => __('marketing.step_3_title'), 'desc' => __('marketing.step_3_desc')],
                        ['num' => '4', 'title' => __('marketing.step_4_title'), 'desc' => __('marketing.step_4_desc')],
                    ];
                @endphp
                @foreach($steps as $step)
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-600 text-white text-2xl font-bold mb-4">
                            {{ $step['num'] }}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $step['title'] }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing Preview --}}
    <x-container class="py-12 border-t sm:py-24 border-zinc-200">
        <x-marketing.sections.pricing />
    </x-container>

    {{-- Testimonials (if available) --}}
    @php
        $showTestimonials = setting('site.show_testimonials', '1') == '1';
        $hasTestimonials = $showTestimonials && \App\Models\Testimonial::active()->count() > 0;
    @endphp
    @if($hasTestimonials)
    <x-container class="py-12 border-t sm:py-24 border-zinc-200">
        <x-marketing.sections.testimonials />
    </x-container>
    @endif

    {{-- FAQ --}}
    <x-container class="py-12 border-t sm:py-24 border-zinc-200">
        <x-marketing.sections.faq />
    </x-container>

    {{-- CTA Section --}}
    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">
                {{ __('marketing.ready_to_start') }}
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                {{ __('marketing.try_free_14_days') }}
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-lg font-semibold uppercase tracking-widest hover:bg-gray-100 transition text-base">
                {{ __('marketing.start_free_trial') }}
            </a>
        </div>
    </section>

</x-layouts.marketing>
