<?php
use function Laravel\Folio\{middleware, name};
use App\Models\ContractTemplate;

middleware(['auth', 'verified']);
name('templates.preview');

// Get template
$templateId = request()->route('id');
$template = ContractTemplate::with(['contractType', 'company'])->findOrFail($templateId);

// Check authorization
if ($template->company->user_id !== auth()->id()) {
    abort(403, 'Unauthorized action.');
}

// Sample data for preview
$sampleData = [
    '{company_name}' => $template->company->name,
    '{company_cui}' => $template->company->cui ?? 'RO12345678',
    '{company_reg_number}' => $template->company->registration_number ?? 'J40/1234/2024',
    '{company_address}' => $template->company->address ?? 'București, Sector 1, Str. Exemplu nr. 1',
    '{party_name}' => 'SC EXEMPLU PARTENER SRL',
    '{party_cui}' => 'RO87654321',
    '{party_address}' => 'Cluj-Napoca, Str. Partener nr. 10',
    '{party_representative}' => 'Ion Popescu',
    '{contract_number}' => 'CNT-0001/' . now()->year,
    '{contract_date}' => now()->format('d.m.Y'),
    '{start_date}' => now()->format('d.m.Y'),
    '{end_date}' => now()->addYear()->format('d.m.Y'),
    '{value}' => number_format(50000, 2, ',', '.'),
    '{currency}' => 'RON',
    '{payment_terms}' => '30 zile de la emiterea facturii',
    '{custom_field_1}' => 'Valoare Personalizată 1',
    '{custom_field_2}' => 'Valoare Personalizată 2',
    '{custom_field_3}' => 'Valoare Personalizată 3',
];

// Replace variables in content
$previewContent = $template->content;
foreach ($sampleData as $key => $value) {
    $previewContent = str_replace($key, $value, $previewContent);
}

// Find any unreplaced variables
preg_match_all('/\{([^}]+)\}/', $previewContent, $matches);
$unreplacedVariables = array_unique($matches[0]);
?>

<x-layouts.app>
    <x-app.container class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Previzualizare Șablon</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $template->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('contracts.create', ['template_id' => $template->id]) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Folosește Șablonul
                </a>
                <a href="{{ route('templates.edit', $template->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Editează
                </a>
                <a href="{{ route('templates.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                    Înapoi
                </a>
            </div>
        </div>

        <!-- Template Info -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tip Contract</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                        {{ $template->contractType->name ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    <p class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $template->is_active ? 'Activ' : 'Inactiv' }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Creat la</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                        {{ $template->created_at->format('d.m.Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Utilizări</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                        {{ $template->contracts()->count() }} contracte
                    </p>
                </div>
            </div>

            @if($template->description)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $template->description }}</p>
                </div>
            @endif
        </div>

        <!-- Warning for unreplaced variables -->
        @if(count($unreplacedVariables) > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                            Variabile nerecunoscute găsite
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                            <p>Următoarele variabile nu au fost recunoscute și nu vor fi înlocuite:</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($unreplacedVariables as $var)
                                    <code class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/40 rounded text-xs">{{ $var }}</code>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Preview Notice -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        Aceasta este o previzualizare cu date exemplu. Când veți crea un contract real, variabilele vor fi înlocuite cu datele efective din contract.
                    </p>
                </div>
            </div>
        </div>

        <!-- Preview Content -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Contract Previzualizare</h2>
            </div>
            <div class="p-8">
                <div class="max-w-4xl mx-auto bg-white dark:bg-gray-900 p-12 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap font-serif text-gray-900 dark:text-gray-100 leading-relaxed">{{ $previewContent }}</div>
                </div>
            </div>
        </div>

        <!-- Sample Data Used -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Date Exemplu Folosite</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($sampleData as $key => $value)
                    <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <code class="text-sm font-mono text-blue-600 dark:text-blue-400 flex-shrink-0">{{ $key }}</code>
                        <span class="text-sm text-gray-600 dark:text-gray-400">→</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-app.container>
</x-layouts.app>
