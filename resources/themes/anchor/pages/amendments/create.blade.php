<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Contract;
use App\Models\Company;

middleware(['auth', 'verified']);
name('amendments.create');

// Get contract
$contractId = request('contract_id');
$contract = Contract::with(['company', 'parties'])->findOrFail($contractId);

// Check authorization
if ($contract->company->user_id !== auth()->id()) {
    abort(403, 'Unauthorized action.');
}

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();
$companyIds = $companies->pluck('id');

// Amendment number - next in sequence for this contract
$lastAmendment = $contract->amendments()->orderBy('amendment_number', 'desc')->first();
$nextNumber = $lastAmendment ? $lastAmendment->amendment_number + 1 : 1;
?>

<x-layouts.app>
    <x-app.container class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Act Adițional Nou</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Contract: {{ $contract->title }} ({{ $contract->contract_number }})
                </p>
            </div>
            <a href="{{ route('contracts.show', $contract->id) }}"
               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Înapoi la Contract
            </a>
        </div>

        <!-- Info Alert -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300">
                        Ce este un act adițional?
                    </h3>
                    <p class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                        Actul adițional modifică anumite clauze ale contractului inițial fără a crea un contract nou. Este parte integrantă a contractului și trebuie semnat de toate părțile.
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('amendments.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="contract_id" value="{{ $contract->id }}">

            <!-- Amendment Details -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Detalii Act Adițional</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Amendment Number -->
                    <div>
                        <label for="amendment_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Număr Act Adițional <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="amendment_number"
                               name="amendment_number"
                               value="{{ old('amendment_number', $nextNumber) }}"
                               required
                               min="1"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Următorul număr disponibil: {{ $nextNumber }}
                        </p>
                    </div>

                    <!-- Effective Date -->
                    <div>
                        <label for="effective_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Data Intrării în Vigoare <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="effective_date"
                               name="effective_date"
                               value="{{ old('effective_date', now()->format('Y-m-d')) }}"
                               required
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Titlu Act Adițional <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="title"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="ex: Prelungire termen de execuție">
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descriere Scurtă
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="2"
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Rezumat al modificărilor aduse...">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- Amendment Content -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Conținut Act Adițional</h2>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Clauze Modificate <span class="text-red-500">*</span>
                    </label>
                    <textarea id="content"
                              name="content"
                              rows="15"
                              required
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                              placeholder="ACT ADIȚIONAL NR. {{ $nextNumber }}
la Contractul {{ $contract->contract_number }}

Părțile contractante au convenit modificarea următoarelor clauze:

Art. 1 - Modificarea clauzei...
Vechea redactare: ...
Noua redactare: ...

Art. 2 - Adăugarea clauzei...
...

Prezentul act adițional face parte integrantă din contractul inițial și intră în vigoare la data semnării de către ambele părți.">{{ old('content') }}</textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Specificați clar care clauze din contractul inițial sunt modificate, adăugate sau eliminate.
                    </p>
                </div>
            </div>

            <!-- Changes Summary -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Rezumat Modificări</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Value Change -->
                    <div>
                        <label for="new_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Valoare Nouă (dacă se modifică)
                        </label>
                        <input type="number"
                               id="new_value"
                               name="new_value"
                               step="0.01"
                               value="{{ old('new_value') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Valoare actuală: {{ number_format($contract->value, 2) }} {{ $contract->currency }}">
                    </div>

                    <!-- End Date Change -->
                    <div>
                        <label for="new_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Dată Sfârșit Nouă (dacă se prelungește)
                        </label>
                        <input type="date"
                               id="new_end_date"
                               name="new_end_date"
                               value="{{ old('new_end_date') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Dată actuală: {{ $contract->end_date?->format('d.m.Y') ?? 'Nedefinită' }}">
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Note Interne
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Note și comentarii interne (nu vor apărea în documentul final)">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Signatories -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Părți Semnatare</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Actul adițional va fi trimis pentru semnare către aceleași părți din contractul inițial:
                </p>
                <div class="space-y-2">
                    @foreach($contract->parties as $party)
                        <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $party->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $party->email }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('contracts.show', $contract->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                    Anulează
                </a>
                <button type="submit"
                        name="action"
                        value="save_draft"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-800 focus:outline-none transition">
                    Salvează ca Ciornă
                </button>
                <button type="submit"
                        name="action"
                        value="send_for_signing"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Creează și Trimite pentru Semnare
                </button>
            </div>
        </form>
    </x-app.container>
</x-layouts.app>
