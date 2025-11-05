<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Company;

middleware(['auth', 'verified']);
name('integrations.index');

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();

// Get active company
$activeCompanyId = session('active_company_id') ?? $companies->first()?->id;
$activeCompany = $companies->firstWhere('id', $activeCompanyId);

// Available integrations
$integrations = [
    'romanian' => [
        'title' => 'Integrări Românești',
        'items' => [
            [
                'name' => 'ANAF e-Factura',
                'description' => 'Transmitere automată a facturilor către SPV ANAF (Spațiul Privat Virtual)',
                'icon' => '🏛️',
                'status' => 'available',
                'features' => ['Validare facturi', 'Transmitere automată', 'Sincronizare status', 'Rapoarte ANAF'],
            ],
            [
                'name' => 'ONRC',
                'description' => 'Verificare automată CUI și date companii din Registrul Comerțului',
                'icon' => '📋',
                'status' => 'available',
                'features' => ['Verificare CUI', 'Extragere date firmă', 'Validare existență', 'Istoric modificări'],
            ],
            [
                'name' => 'Poșta Română',
                'description' => 'Trimite contracte prin poștă cu notificare de primire',
                'icon' => '📮',
                'status' => 'planned',
                'features' => ['Trimitere contracte', 'Track & trace', 'Confirmare primire', 'Arhivare dovezi'],
            ],
        ],
    ],
    'accounting' => [
        'title' => 'Software Contabilitate',
        'items' => [
            [
                'name' => 'SmartBill',
                'description' => 'Sincronizare facturi și clienți cu SmartBill',
                'icon' => '💳',
                'status' => 'available',
                'features' => ['Export facturi', 'Import clienți', 'Sincronizare plăți', 'Rapoarte financiare'],
            ],
            [
                'name' => 'Oblio',
                'description' => 'Integrare completă cu platforma de facturare Oblio',
                'icon' => '💰',
                'status' => 'available',
                'features' => ['Facturare automată', 'Gestiune stocuri', 'e-Factura integrată', 'NIR/Avize'],
            ],
            [
                'name' => 'Saga',
                'description' => 'Export date către programul de contabilitate Saga',
                'icon' => '📊',
                'status' => 'planned',
                'features' => ['Export contracte', 'Export facturi', 'Plan de conturi', 'Jurnale'],
            ],
            [
                'name' => 'FGO',
                'description' => 'Sincronizare cu software-ul de contabilitate FGO',
                'icon' => '📈',
                'status' => 'planned',
                'features' => ['Import/Export date', 'Sincronizare clienți', 'Balanțe', 'Raportări'],
            ],
        ],
    ],
    'crm' => [
        'title' => 'CRM & Sales',
        'items' => [
            [
                'name' => 'HubSpot',
                'description' => 'Sincronizare contracte și clienți cu HubSpot CRM',
                'icon' => '🎯',
                'status' => 'available',
                'features' => ['Sincronizare contacte', 'Pipeline deals', 'Automatizări', 'Raportare vânzări'],
            ],
            [
                'name' => 'Salesforce',
                'description' => 'Integrare completă cu Salesforce Sales Cloud',
                'icon' => '☁️',
                'status' => 'available',
                'features' => ['Sincronizare bi-direcțională', 'Workflow automation', 'Custom objects', 'Einstein AI'],
            ],
            [
                'name' => 'Zoho CRM',
                'description' => 'Conectare cu platforma Zoho pentru management clienți',
                'icon' => '🔷',
                'status' => 'available',
                'features' => ['Leads & contacts', 'Deals tracking', 'Email integration', 'Analytics'],
            ],
            [
                'name' => 'Pipedrive',
                'description' => 'Sincronizare pipeline vânzări cu Pipedrive',
                'icon' => '🔴',
                'status' => 'available',
                'features' => ['Visual pipeline', 'Activity tracking', 'Email sync', 'Mobile app'],
            ],
        ],
    ],
    'storage' => [
        'title' => 'Stocare & Documente',
        'items' => [
            [
                'name' => 'Google Drive',
                'description' => 'Salvare automată contracte în Google Drive',
                'icon' => '💾',
                'status' => 'available',
                'features' => ['Backup automat', 'Organizare foldere', 'Partajare securizată', 'Versioning'],
            ],
            [
                'name' => 'Dropbox',
                'description' => 'Sincronizare documentelor cu Dropbox Business',
                'icon' => '📦',
                'status' => 'available',
                'features' => ['Cloud storage', 'Team folders', 'Smart sync', 'File recovery'],
            ],
            [
                'name' => 'OneDrive',
                'description' => 'Integrare cu Microsoft OneDrive pentru afaceri',
                'icon' => '☁️',
                'status' => 'available',
                'features' => ['Office 365 integration', 'SharePoint sync', 'Colaborare', 'Securitate'],
            ],
            [
                'name' => 'Box',
                'description' => 'Stocare enterprise cu Box',
                'icon' => '📥',
                'status' => 'planned',
                'features' => ['Enterprise storage', 'Compliance', 'Workflows', 'Security'],
            ],
        ],
    ],
    'communication' => [
        'title' => 'Comunicare & Colaborare',
        'items' => [
            [
                'name' => 'Slack',
                'description' => 'Notificări și alerteîn canalele Slack ale echipei',
                'icon' => '💬',
                'status' => 'available',
                'features' => ['Notificări contracte', 'Alerts workflow', 'Bot commands', 'Team channels'],
            ],
            [
                'name' => 'Microsoft Teams',
                'description' => 'Integrare cu Microsoft Teams pentru colaborare',
                'icon' => '👥',
                'status' => 'available',
                'features' => ['Teams notifications', 'Channel integration', 'Video calls', 'File sharing'],
            ],
            [
                'name' => 'Gmail',
                'description' => 'Trimitere email-uri prin cont Gmail de business',
                'icon' => '✉️',
                'status' => 'available',
                'features' => ['Send contracts', 'Email tracking', 'Templates', 'Signatures'],
            ],
            [
                'name' => 'SendGrid',
                'description' => 'Email transacțional profesional prin SendGrid',
                'icon' => '📧',
                'status' => 'available',
                'features' => ['Deliverability', 'Email analytics', 'Templates', 'API access'],
            ],
        ],
    ],
    'payments' => [
        'title' => 'Plăți & Procesare',
        'items' => [
            [
                'name' => 'Stripe',
                'description' => 'Procesare plăți online cu Stripe',
                'icon' => '💳',
                'status' => 'available',
                'features' => ['Card payments', 'SEPA transfers', 'Subscriptions', 'Invoicing'],
            ],
            [
                'name' => 'PayU România',
                'description' => 'Gateway de plăți local PayU',
                'icon' => '🇷🇴',
                'status' => 'available',
                'features' => ['Plăți cu cardul', 'Rate', 'Rambursări', 'Rapoarte'],
            ],
            [
                'name' => 'Netopia Payments',
                'description' => 'Procesare plăți cu Netopia (fost mobilPay)',
                'icon' => '💰',
                'status' => 'available',
                'features' => ['Multiple payment methods', 'Split payments', 'Recurring', 'Mobile'],
            ],
            [
                'name' => 'OP Transfer',
                'description' => 'Transfer bancar instant în România',
                'icon' => '🏦',
                'status' => 'planned',
                'features' => ['Instant transfer', 'QR payments', 'Bank integration', 'Reconciliation'],
            ],
        ],
    ],
    'other' => [
        'title' => 'Alte Integrări',
        'items' => [
            [
                'name' => 'Zapier',
                'description' => 'Conectează ContractRO cu 5000+ aplicații prin Zapier',
                'icon' => '⚡',
                'status' => 'available',
                'features' => ['5000+ apps', 'No-code automation', 'Multi-step zaps', 'Triggers & actions'],
            ],
            [
                'name' => 'Make (Integromat)',
                'description' => 'Automatizări avansate cu Make',
                'icon' => '🔗',
                'status' => 'available',
                'features' => ['Visual automation', 'Complex workflows', 'Data transformation', 'API access'],
            ],
            [
                'name' => 'Webhooks',
                'description' => 'Webhooks personalizate pentru evenimente din platformă',
                'icon' => '🔔',
                'status' => 'available',
                'features' => ['Real-time events', 'Custom endpoints', 'JSON payloads', 'Retry logic'],
            ],
            [
                'name' => 'API REST',
                'description' => 'API public RESTful pentru dezvoltatori',
                'icon' => '🔧',
                'status' => 'available',
                'features' => ['Full API access', 'Documentation', 'Rate limits', 'Authentication'],
            ],
        ],
    ],
];

// Status badges
$statusBadges = [
    'available' => ['text' => __('common.available'), 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
    'planned' => ['text' => __('common.coming_soon'), 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
    'beta' => ['text' => __('common.beta'), 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
];
?>

<x-layouts.app>
    <x-app.container class="space-y-6">
        <!-- Page Header -->
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white">{{ __('common.integrations') }}</h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                {{ __('common.integrations_description') }}
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">30+</div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('common.integrations_available') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">5000+</div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('common.apps_via_zapier') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">API</div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('common.full_rest_api') }}</div>
            </div>
        </div>

        <!-- Integrations Categories -->
        @foreach($integrations as $categoryKey => $category)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ $category['title'] }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($category['items'] as $integration)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-lg transition">
                            <div class="flex items-start justify-between mb-4">
                                <div class="text-4xl">{{ $integration['icon'] }}</div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadges[$integration['status']]['class'] }}">
                                    {{ $statusBadges[$integration['status']]['text'] }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $integration['name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ $integration['description'] }}
                            </p>
                            <div class="space-y-2 mb-4">
                                @foreach(array_slice($integration['features'], 0, 3) as $feature)
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $feature }}
                                    </div>
                                @endforeach
                            </div>
                            @if($integration['status'] === 'available')
                                <a href="{{ route('integrations.configure', ['integration' => Str::slug($integration['name'])]) }}"
                                   class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition">
                                    {{ __('common.configure') }}
                                </a>
                            @elseif($integration['status'] === 'planned')
                                <button disabled
                                        class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest cursor-not-allowed">
                                    {{ __('common.coming_soon') }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Custom Integration CTA -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-xl p-8 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">{{ __('common.need_custom_integration') }}</h2>
            <p class="text-lg mb-6 opacity-90">
                {{ __('common.custom_integration_description') }}
            </p>
            <a href="{{ route('contact') }}"
               class="inline-flex items-center px-6 py-3 bg-white text-blue-600 rounded-md font-semibold text-sm uppercase tracking-widest hover:bg-gray-100 active:bg-gray-200 focus:outline-none transition">
                {{ __('common.contact_us') }}
            </a>
        </div>
    </x-app.container>
</x-layouts.app>
