<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Company;
use App\Models\ContractType;

middleware(['auth', 'verified']);
name('templates.create');

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();
$companyIds = $companies->pluck('id');

// Get active company
$activeCompanyId = session('active_company_id') ?? $companies->first()?->id;

// Get contract types
$contractTypes = ContractType::whereIn('company_id', $companyIds)->get();

// Available variables for templates
$availableVariables = [
    ['key' => '{company_name}', 'description' => 'Numele companiei'],
    ['key' => '{company_cui}', 'description' => 'CUI companie'],
    ['key' => '{company_reg_number}', 'description' => 'Nr. reg. comert'],
    ['key' => '{company_address}', 'description' => 'Adresa companie'],
    ['key' => '{party_name}', 'description' => 'Numele partii'],
    ['key' => '{party_cui}', 'description' => 'CUI parte'],
    ['key' => '{party_address}', 'description' => 'Adresa parte'],
    ['key' => '{party_representative}', 'description' => 'Reprezentant parte'],
    ['key' => '{contract_number}', 'description' => 'Număr contract'],
    ['key' => '{contract_date}', 'description' => 'Data contract'],
    ['key' => '{start_date}', 'description' => 'Data start'],
    ['key' => '{end_date}', 'description' => 'Data sfârșit'],
    ['key' => '{value}', 'description' => 'Valoare contract'],
    ['key' => '{currency}', 'description' => 'Moneda'],
    ['key' => '{payment_terms}', 'description' => 'Termeni de plată'],
    ['key' => '{custom_field_1}', 'description' => 'Câmp personalizat 1'],
    ['key' => '{custom_field_2}', 'description' => 'Câmp personalizat 2'},
    ['key' => '{custom_field_3}', 'description' => 'Câmp personalizat 3'},
];
?>

<x-layouts.app>
    <x-app.container class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Șablon Nou</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Creați un șablon reutilizabil de contract</p>
            </div>
            <a href="{{ route('templates.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Înapoi
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('templates.store') }}" class="space-y-6">
                    @csrf

                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Informații de bază</h2>

                        <!-- Company -->
                        <input type="hidden" name="company_id" value="{{ $activeCompanyId }}">

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nume Șablon <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="ex: Contract de prestări servicii">
                        </div>

                        <!-- Contract Type -->
                        <div>
                            <label for="contract_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tip Contract <span class="text-red-500">*</span>
                            </label>
                            <select id="contract_type_id"
                                    name="contract_type_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selectați tipul</option>
                                @foreach($contractTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Descriere
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="Descrierea scurtă a șablonului"></textarea>
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   checked
                                   class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Șablon activ (disponibil pentru utilizare)
                            </label>
                        </div>
                    </div>

                    <!-- Template Content -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Conținut Șablon</h2>
                            <button type="button"
                                    x-data
                                    @click="$dispatch('show-variables')"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                Vezi variabile disponibile →
                            </button>
                        </div>

                        <!-- Content Editor -->
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Conținut Contract <span class="text-red-500">*</span>
                            </label>
                            <textarea id="content"
                                      name="content"
                                      rows="20"
                                      required
                                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                      placeholder="Introduceți conținutul contractului aici. Folosiți variabilele din format {variable_name} pentru a insera date dinamice.

Exemplu:
CONTRACT DE PRESTĂRI SERVICII
Nr. {contract_number} din {contract_date}

Între:
{company_name}, CUI: {company_cui}, cu sediul în {company_address}
și
{party_name}, CUI: {party_cui}, cu sediul în {party_address}

S-a încheiat prezentul contract cu următoarele clauze:
..."></textarea>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Folosiți variabilele în format {variable_name}. Acestea vor fi înlocuite automat cu datele din contract.
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('templates.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                            Anulează
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvează Șablon
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar - Available Variables -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 sticky top-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Variabile Disponibile</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Click pe o variabilă pentru a o copia în clipboard:
                    </p>
                    <div class="space-y-2 max-h-[600px] overflow-y-auto">
                        @foreach($availableVariables as $variable)
                            <button type="button"
                                    x-data
                                    @click="
                                        navigator.clipboard.writeText('{{ $variable['key'] }}');
                                        $dispatch('notify', { message: 'Variabilă copiată!', type: 'success' });
                                    "
                                    class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <code class="text-sm font-mono text-blue-600 dark:text-blue-400">
                                            {{ $variable['key'] }}
                                        </code>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $variable['description'] }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 ml-2 flex-shrink-0"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <!-- Variable Usage Tips -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">💡 Sfaturi</h4>
                        <ul class="text-xs text-blue-800 dark:text-blue-400 space-y-1">
                            <li>• Variabilele sunt case-sensitive</li>
                            <li>• Pot fi folosite oriunde în text</li>
                            <li>• Vor fi înlocuite automat la generare</li>
                            <li>• Dacă lipsește valoarea, se va afișa "-"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-app.container>
</x-layouts.app>
