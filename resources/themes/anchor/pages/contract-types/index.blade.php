<?php
use function Laravel\Folio\{middleware, name};
use App\Models\ContractType;
use App\Models\Company;

middleware(['auth', 'verified']);
name('contract-types.index');

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();
$companyIds = $companies->pluck('id');

// Get active company
$activeCompanyId = session('active_company_id') ?? $companies->first()?->id;

// Get contract types
$contractTypes = ContractType::whereIn('company_id', $companyIds)
    ->withCount('contracts')
    ->orderBy('name')
    ->get();
?>

<x-layouts.app>
    <x-app.container class="space-y-6" x-data="contractTypesManager()">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tipuri Contracte</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Gestionați tipurile de contracte disponibile</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button @click="showAddForm = true"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tip Nou
                </button>
            </div>
        </div>

        <!-- Add New Type Form -->
        <div x-show="showAddForm"
             x-cloak
             class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Adaugă Tip Nou</h3>
            <form method="POST" action="{{ route('contract-types.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="company_id" value="{{ $activeCompanyId }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nume Tip <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               required
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                               placeholder="ex: Contract de prestări servicii">
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Cod <span class="text-gray-500">(opțional)</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                               placeholder="ex: SERV">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descriere
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Descrierea tipului de contract"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button"
                            @click="showAddForm = false"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                        Anulează
                    </button>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        Salvează
                    </button>
                </div>
            </form>
        </div>

        <!-- Contract Types List -->
        @if($contractTypes->count() > 0)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nume
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Cod
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Descriere
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Utilizări
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Acțiuni</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($contractTypes as $type)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                x-data="{ editing: false }">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-show="!editing">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $type->name }}
                                        </div>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <input type="text"
                                               x-model="editData.name"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-show="!editing">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $type->code ?? '-' }}
                                        </div>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <input type="text"
                                               x-model="editData.code"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div x-show="!editing">
                                        <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ $type->description ?? '-' }}
                                        </div>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <textarea x-model="editData.description"
                                                  rows="2"
                                                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $type->contracts_count }} contracte
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div x-show="!editing" class="flex items-center justify-end gap-3">
                                        <button type="button"
                                                @click="startEdit({{ $type->id }}, '{{ addslashes($type->name) }}', '{{ addslashes($type->code ?? '') }}', '{{ addslashes($type->description ?? '') }}')"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                            Editează
                                        </button>
                                        @if($type->contracts_count == 0)
                                            <form method="POST"
                                                  action="{{ route('contract-types.destroy', $type->id) }}"
                                                  onsubmit="return confirm('Sigur doriți să ștergeți acest tip de contract?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                    Șterge
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 cursor-not-allowed" title="Nu se poate șterge - există contracte asociate">
                                                Șterge
                                            </span>
                                        @endif
                                    </div>
                                    <div x-show="editing" x-cloak class="flex items-center justify-end gap-3">
                                        <button type="button"
                                                @click="cancelEdit()"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
                                            Anulează
                                        </button>
                                        <form method="POST" action="{{ route('contract-types.update', $type->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" :value="editData.name">
                                            <input type="hidden" name="code" :value="editData.code">
                                            <input type="hidden" name="description" :value="editData.description">
                                            <button type="submit"
                                                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                                Salvează
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Niciun tip de contract</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Începeți prin a adăuga primul tip de contract.</p>
                <div class="mt-6">
                    <button @click="showAddForm = true"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Adaugă primul tip
                    </button>
                </div>
            </div>
        @endif>

        <!-- Common Contract Types Suggestions -->
        @if($contractTypes->count() == 0)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-3">
                    💡 Tipuri comune de contracte în România:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @php
                        $commonTypes = [
                            ['name' => 'Contract de prestări servicii', 'code' => 'SERV'],
                            ['name' => 'Contract de vânzare-cumpărare', 'code' => 'VC'],
                            ['name' => 'Contract de muncă (CIM)', 'code' => 'CIM'],
                            ['name' => 'Contract de închiriere', 'code' => 'INCH'],
                            ['name' => 'Contract de comodat', 'code' => 'COM'],
                            ['name' => 'Contract de colaborare', 'code' => 'COL'],
                            ['name' => 'Contract de parteneriat', 'code' => 'PART'],
                            ['name' => 'Contract de consultanță', 'code' => 'CONS'],
                            ['name' => 'Contract de distribuție', 'code' => 'DIST'],
                        ];
                    @endphp
                    @foreach($commonTypes as $suggestedType)
                        <div class="text-sm text-blue-800 dark:text-blue-400">
                            • {{ $suggestedType['name'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-app.container>

    <script>
        function contractTypesManager() {
            return {
                showAddForm: false,
                editData: {
                    id: null,
                    name: '',
                    code: '',
                    description: ''
                },
                startEdit(id, name, code, description) {
                    // Find the row and set editing to true
                    this.editData = {
                        id: id,
                        name: name,
                        code: code,
                        description: description
                    };

                    // Set editing flag on the specific row
                    this.$el.querySelectorAll('[x-data]').forEach(el => {
                        if (el._x_dataStack && el._x_dataStack[0].editing !== undefined) {
                            el._x_dataStack[0].editing = false;
                        }
                    });

                    event.target.closest('tr')._x_dataStack[0].editing = true;
                },
                cancelEdit() {
                    event.target.closest('tr')._x_dataStack[0].editing = false;
                    this.editData = { id: null, name: '', code: '', description: '' };
                }
            }
        }
    </script>
</x-layouts.app>
