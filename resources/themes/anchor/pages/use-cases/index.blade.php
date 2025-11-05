<?php
use function Laravel\Folio\{name};
name('use-cases');
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Cazuri de Utilizare - ContractRO',
        'description' => 'Descoperă cum ContractRO ajută afaceri din diverse industrii să gestioneze contracte mai eficient.',
    ]"
>

    <section class="py-20 bg-gradient-to-br from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold text-white mb-6">
                Cazuri de Utilizare
            </h1>
            <p class="text-xl text-blue-100">
                ContractRO se adaptează nevoilor din orice industrie
            </p>
        </div>
    </section>

    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @php
                $useCases = [
                    [
                        'icon' => '💼',
                        'title' => 'Prestări Servicii & Freelancing',
                        'description' => 'Pentru consultanți, dezvoltatori, designeri și profesioniști independenți',
                        'features' => [
                            'Contract de prestări servicii personalizabil',
                            'Facturare automată per proiect',
                            'Rapoarte venituri per client',
                            'Reminder plăți clienți'
                        ]
                    ],
                    [
                        'icon' => '🏢',
                        'title' => 'Agenții & Studiouri',
                        'description' => 'Pentru agenții de marketing, web design, software development',
                        'features' => [
                            'Management clienți multiple',
                            'Contracte pe proiecte',
                            'Acte adiționale pentru modificări',
                            'Rapoarte profitabilitate'
                        ]
                    ],
                    [
                        'icon' => '🏠',
                        'title' => 'Real Estate & Închirieri',
                        'description' => 'Pentru proprietari, administratori și agenții imobiliare',
                        'features' => [
                            'Contract de închiriere',
                            'Contract vânzare-cumpărare',
                            'Garanții și anexe',
                            'Arhivare digitală contracte'
                        ]
                    ],
                    [
                        'icon' => '👥',
                        'title' => 'HR & Resurse Umane',
                        'description' => 'Pentru departamente HR și firme de recrutare',
                        'features' => [
                            'Contract Individual de Muncă (CIM)',
                            'Acte adiționale salariu',
                            'Suspendare contract',
                            'Documentație angajați'
                        ]
                    ],
                    [
                        'icon' => '🚚',
                        'title' => 'Logistică & Transport',
                        'description' => 'Pentru firme de transport și logistică',
                        'features' => [
                            'Contracte transport mărfuri',
                            'CMR și documente transport',
                            'Contracte colaboratori',
                            'Factură din contract'
                        ]
                    ],
                    [
                        'icon' => '🌾',
                        'title' => 'Agricultură',
                        'description' => 'Pentru fermieri și producători agricoli',
                        'features' => [
                            'Contract vânzare-cumpărare produse',
                            'Contract muncă sezonieră',
                            'Contracte arendă teren',
                            'Facturare agricole'
                        ]
                    ],
                    [
                        'icon' => '🏥',
                        'title' => 'Sănătate & Medical',
                        'description' => 'Pentru cabinete medicale și clinici',
                        'features' => [
                            'Contracte pacienți',
                            'Contracte furnizori',
                            'Contracte personal medical',
                            'GDPR compliant'
                        ]
                    ],
                    [
                        'icon' => '🎓',
                        'title' => 'Educație & Training',
                        'description' => 'Pentru școli, academii și instructori',
                        'features' => [
                            'Contracte cursanți',
                            'Contracte instructori',
                            'Certificate automatizate',
                            'Plăți recurente'
                        ]
                    ]
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($useCases as $useCase)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 hover:shadow-lg transition">
                        <div class="text-5xl mb-4">{{ $useCase['icon'] }}</div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $useCase['title'] }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ $useCase['description'] }}
                        </p>
                        <ul class="space-y-2">
                            @foreach($useCase['features'] as $feature)
                                <li class="flex items-start text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">
                Industria ta nu e listată?
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                ContractRO funcționează pentru orice tip de afacere. Testează gratuit!
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-lg font-semibold uppercase tracking-widest hover:bg-gray-100 transition text-base">
                Începe Acum Gratuit
            </a>
        </div>
    </section>

</x-layouts.marketing>
