<?php
use function Laravel\Folio\{name};
name('features');
?>

<x-layouts.marketing
    :seo="[
        'title'         => 'Funcționalități - ContractRO',
        'description'   => 'Toate funcționalitățile platformei ContractRO: contracte electronice, semnătură validată SMS, generare facturi, rapoarte financiare și multe altele.',
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>

    {{-- Hero --}}
    <section class="py-20 bg-gradient-to-br from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold text-white mb-6">
                Tot ce ai nevoie pentru contracte
            </h1>
            <p class="text-xl text-blue-100">
                Soluție completă de management contracte, de la creare până la arhivare
            </p>
        </div>
    </section>

    {{-- Features Grid --}}
    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @php
                $features = [
                    [
                        'icon' => '📝',
                        'title' => 'Șabloane Contracte',
                        'description' => 'Biblioteca cu sute de șabloane legale pentru toate tipurile de contracte românești',
                        'points' => [
                            'Contract de prestări servicii',
                            'Contract de vânzare-cumpărare',
                            'Contract de închiriere',
                            'Contract de colaborare',
                            'Contract de muncă (CIM)',
                            'Contract de comodat',
                            'Șabloane personalizate cu variabile'
                        ]
                    ],
                    [
                        'icon' => '✍️',
                        'title' => 'Semnătură Electronică',
                        'description' => 'Sistem de semnare securizat conform EU eIDAS Regulation 910/2014',
                        'points' => [
                            'Validare SMS cu cod unic',
                            'Semnătură scrisă de mână',
                            'Semnătură digitală certificată',
                            'Workflow multi-părți',
                            'Stocare metadata (IP, timestamp)',
                            'Dovadă legală de semnare',
                            'Notificări automate'
                        ]
                    ],
                    [
                        'icon' => '🏢',
                        'title' => 'Management Multi-Companie',
                        'description' => 'Gestionează contractele pentru mai multe entități legale dintr-un singur cont',
                        'points' => [
                            'Companii nelimitate (plan Pro)',
                            'Schimbare rapidă între companii',
                            'Date fiscale separate per companie',
                            'Facturare separată',
                            'Rapoarte per companie',
                            'Logo și branding per companie'
                        ]
                    ],
                    [
                        'icon' => '💰',
                        'title' => 'Generare Facturi',
                        'description' => 'Creează facturi automat din contracte, cu integrare ANAF e-Factura',
                        'points' => [
                            'Facturare automată din contract',
                            'Format fiscal românesc',
                            'TVA 19% automat',
                            'Serii facturi per companie',
                            'Integrare ANAF e-Factura',
                            'Facturi recurente',
                            'Export PDF profesional'
                        ]
                    ],
                    [
                        'icon' => '📊',
                        'title' => 'Rapoarte și Analize',
                        'description' => 'Analize complete pentru profitabilitate și management financiar',
                        'points' => [
                            'Raport profitabilitate',
                            'Analiză venituri per tip contract',
                            'Statistici contracte (active/expirate)',
                            'Previziuni financiare',
                            'Analiză clienți',
                            'Export Excel și PDF',
                            'Grafice interactive'
                        ]
                    ],
                    [
                        'icon' => '🔒',
                        'title' => 'Securitate și Conformitate',
                        'description' => 'Protecție maximă și conformitate cu reglementările europene',
                        'points' => [
                            'Encriptare SSL/TLS',
                            'Backup automat zilnic',
                            'Conformitate GDPR',
                            'Conformitate EU eIDAS',
                            'Audit trail complet',
                            'Autentificare în doi pași (2FA)',
                            'Permisiuni pe roluri'
                        ]
                    ],
                    [
                        'icon' => '🔍',
                        'title' => 'Căutare Avansată',
                        'description' => 'Găsește orice contract instant cu peste 30 de filtre',
                        'points' => [
                            'Full-text search',
                            'Filtrare după companie, tip, status',
                            'Filtrare după parte, CUI, valoare',
                            'Filtrare după dată (creare/semnare/expirare)',
                            'Filtrare după atașamente',
                            'Salvare căutări frecvente',
                            'Export rezultate căutare'
                        ]
                    ],
                    [
                        'icon' => '📎',
                        'title' => 'Atașamente',
                        'description' => 'Atașează orice document sau fișier la contracte',
                        'points' => [
                            'Upload multiple fișiere',
                            'Suport PDF, Word, Excel, imagini',
                            'Organizare automată',
                            'Versioning documente',
                            'Preview în browser',
                            'Download lot',
                            'Stocare cloud securizată'
                        ]
                    ],
                    [
                        'icon' => '📋',
                        'title' => 'Acte Adiționale',
                        'description' => 'Modifică contractele existente cu acte adiționale legale',
                        'points' => [
                            'Creare act adițional simplu',
                            'Referință la contract inițial',
                            'Numerotare automată',
                            'Workflow semnare',
                            'Istorc modificări',
                            'PDF act adițional',
                            'Arhivare automată'
                        ]
                    ],
                    [
                        'icon' => '✅',
                        'title' => 'Task Management',
                        'description' => 'Gestionează taskuri și termene legate de contracte',
                        'points' => [
                            'Taskuri per contract',
                            'Asignare către utilizatori',
                            'Termene și reminder',
                            'Status tracking',
                            'Prioritizare taskuri',
                            'Dashboard taskuri',
                            'Notificări email'
                        ]
                    ],
                    [
                        'icon' => '🔗',
                        'title' => 'Integrări',
                        'description' => 'Conectează cu instrumentele tale favorite de business',
                        'points' => [
                            'ANAF e-Factura',
                            'ONRC (verificare CUI)',
                            'SmartBill, Oblio',
                            'Google Drive, Dropbox',
                            'Slack, Microsoft Teams',
                            'Stripe, PayU, Netopia',
                            'API REST complet'
                        ]
                    ],
                    [
                        'icon' => '📱',
                        'title' => 'Mobile-Friendly',
                        'description' => 'Accesează contractele de pe orice dispozitiv',
                        'points' => [
                            'Design responsive',
                            'Semnare pe mobile',
                            'Notificări push',
                            'Acces oriunde, oricând',
                            'Sincronizare automată',
                            'Aplicație mobilă (în curând)'
                        ]
                    ],
                    [
                        'icon' => '👥',
                        'title' => 'Colaborare Echipă',
                        'description' => 'Lucrează împreună cu echipa ta la contracte',
                        'points' => [
                            'Invită membri echipă',
                            'Roluri și permisiuni',
                            'Comentarii interne',
                            '@mentions',
                            'Istorić activitate',
                            'Partajare contracte',
                            'Colaborare real-time'
                        ]
                    ],
                    [
                        'icon' => '📧',
                        'title' => 'Notificări Automate',
                        'description' => 'Nu mai uita niciun termen sau eveniment important',
                        'points' => [
                            'Contract trimis pentru semnare',
                            'Contract semnat',
                            'Contract expiră în X zile',
                            'Task asignat',
                            'Task aproape de termen',
                            'Factură neachitată',
                            'Reminder personalizabile'
                        ]
                    ],
                    [
                        'icon' => '🌍',
                        'title' => 'Multi-Limbă',
                        'description' => 'Interfață disponibilă în română și engleză',
                        'points' => [
                            'Interfață română completă',
                            'Interfață engleză',
                            'Șabloane în ambele limbi',
                            'Suport în limba română',
                            'Documentație română',
                            'Terminologie juridică corectă'
                        ]
                    ]
                ];
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach($features as $feature)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 hover:shadow-xl transition">
                        <div class="flex items-start">
                            <div class="text-5xl mr-6 flex-shrink-0">{{ $feature['icon'] }}</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                    {{ $feature['title'] }}
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">
                                    {{ $feature['description'] }}
                                </p>
                                <ul class="space-y-2">
                                    @foreach($feature['points'] as $point)
                                        <li class="flex items-start text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $point }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">
                Pregătit să testezi ContractRO?
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                Încearcă gratuit toate funcționalitățile timp de 14 zile
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-lg font-semibold uppercase tracking-widest hover:bg-gray-100 transition text-base">
                Testează Gratuit Acum
            </a>
        </div>
    </section>

</x-layouts.marketing>
