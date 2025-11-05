<?php
use function Laravel\Folio\{middleware, name};
use App\Models\ContractTemplate;
use App\Models\Company;
use App\Models\ContractType;

middleware(['auth', 'verified']);
name('templates.edit');

// Get template
$templateId = request()->route('id');
$template = ContractTemplate::findOrFail($templateId);

// Check authorization
if ($template->company->user_id !== auth()->id()) {
    abort(403, 'Unauthorized action.');
}

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();
$companyIds = $companies->pluck('id');

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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editează Șablon</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $template->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('templates.preview', $template->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Previzualizează
                </a>
                <a href="{{ route('templates.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Înapoi
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('templates.update', $template->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Informații de bază</h2>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nume Șablon <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $template->name) }}"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
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
                                    <option value="{{ $type->id }}" {{ old('contract_type_id', $template->contract_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
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
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('description', $template->description) }}</textarea>
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Șablon activ (disponibil pentru utilizare)
                            </label>
                        </div>

                        <!-- Usage Stats -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Utilizări totale</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $template->contracts()->count() }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Ultima modificare</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $template->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Content -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Conținut Șablon</h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ strlen($template->content) }} caractere
                            </span>
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
                                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono text-sm">{{ old('content', $template->content) }}</textarea>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Folosiți variabilele în format {variable_name}. Acestea vor fi înlocuite automat cu datele din contract.
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between">
                        <form method="POST"
                              action="{{ route('templates.destroy', $template->id) }}"
                              onsubmit="return confirm('Sigur doriți să ștergeți acest șablon? Această acțiune nu poate fi anulată.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Șterge Șablon
                            </button>
                        </form>

                        <div class="flex items-center gap-4">
                            <a href="{{ route('templates.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                                Anulează
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Salvează Modificările
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sidebar - Available Variables -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 sticky top-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Variabile Disponibile</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Click pe o variabilă pentru a o copia:
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
