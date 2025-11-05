<?php
    use function Laravel\Folio\{name};
    name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => 'ContractRO - Platforma Românească de Gestiune Contracte',
        'description'   => 'Creează și semnează contracte în 1 minut. Soluție completă pentru contracte electronice cu semnătură electronică validată SMS. Conform EU eIDAS.',
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
                        Creează și semnează contracte în
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">1 minut</span>
                    </h1>
                    <p class="mt-6 text-xl text-gray-600 dark:text-gray-300">
                        Platforma românească completă pentru gestiunea contractelor și semnături electronice. 100% legal, conform EU eIDAS.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 border border-transparent rounded-lg font-semibold text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition text-base">
                            Testează Gratuit 14 Zile
                        </a>
                        <a href="#features"
                           class="inline-flex items-center justify-center px-8 py-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 transition text-base">
                            Vezi Funcționalități
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        ✓ Fără card necesar  ✓ Configurare în 2 minute  ✓ Suport în română
                    </p>
                </div>
                <div class="relative">
                    <div class="aspect-w-16 aspect-h-12 rounded-2xl shadow-2xl overflow-hidden bg-white dark:bg-gray-800 p-4">
                        <img src="/images/dashboard-preview.png" alt="ContractRO Dashboard" class="rounded-lg" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white">Tot ce ai nevoie pentru contracte</h2>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">Soluție completă, de la creare până la semnare</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $features = [
                        ['icon' => '📝', 'title' => 'Șabloane Contracte', 'desc' => 'Sute de șabloane legale pentru toate tipurile de contracte românești'],
                        ['icon' => '✍️', 'title' => 'Semnătură Electronică', 'desc' => 'Semnare cu SMS validat, conform EU eIDAS Regulation 910/2014'],
                        ['icon' => '🏢', 'title' => 'Multi-Companie', 'desc' => 'Gestionează contractele pentru mai multe entități legale dintr-un singur cont'],
                        ['icon' => '💰', 'title' => 'Generare Facturi', 'desc' => 'Creează facturi automat din contracte, cu integrare ANAF e-Factura'],
                        ['icon' => '📊', 'title' => 'Rapoarte Financiare', 'desc' => 'Analize complete: profitabilitate, venituri, statistici contracte'],
                        ['icon' => '🔒', 'title' => '100% Securizat', 'desc' => 'Stocare cloud encriptată, backup automat, conformitate GDPR'],
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
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white">Cum funcționează</h2>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">4 pași simpli către contractul semnat</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                @php
                    $steps = [
                        ['num' => '1', 'title' => 'Creează Contul', 'desc' => 'Înregistrare gratuită în 2 minute'],
                        ['num' => '2', 'title' => 'Generează Contractul', 'desc' => 'Alege șablon sau creează unul nou'],
                        ['num' => '3', 'title' => 'Trimite pentru Semnare', 'desc' => 'Validare SMS securizată'],
                        ['num' => '4', 'title' => 'Primești Contractul', 'desc' => 'Contract semnat legal și arhivat'],
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
                Gata să începi?
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                Încearcă ContractRO gratuit 14 zile. Fără card necesar.
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-lg font-semibold uppercase tracking-widest hover:bg-gray-100 transition text-base">
                Testează Gratuit Acum
            </a>
        </div>
    </section>

</x-layouts.marketing>
