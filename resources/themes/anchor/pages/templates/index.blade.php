<?php
use function Laravel\Folio\{middleware, name};
use App\Models\ContractTemplate;
use App\Models\Company;

middleware(['auth', 'verified']);
name('templates.index');

// Get user's companies
$user = auth()->user();
$companies = Company::where('user_id', $user->id)->get();
$companyIds = $companies->pluck('id');

// Get active company
$activeCompanyId = session('active_company_id') ?? $companies->first()?->id;

// Get search parameters
$search = request('search');
$typeFilter = request('type');
$statusFilter = request('status');

// Build query
$query = ContractTemplate::whereIn('company_id', $companyIds)
    ->with(['contractType', 'company']);

if ($search) {
    $query->where(function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%");
    });
}

if ($typeFilter) {
    $query->where('contract_type_id', $typeFilter);
}

if ($statusFilter) {
    $query->where('is_active', $statusFilter === 'active');
}

$templates = $query->orderBy('created_at', 'desc')->paginate(15);

// Get contract types for filter
$contractTypes = \App\Models\ContractType::whereIn('company_id', $companyIds)->get();
?>

<x-layouts.app>
    <x-app.container class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Șabloane Contracte</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Gestionați șabloanele de contracte reutilizabile</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('templates.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Șablon Nou
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Căutare</label>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Căutați după nume sau descriere..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tip Contract</label>
                    <select name="type"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate tipurile</option>
                        @foreach($contractTypes as $type)
                            <option value="{{ $type->id }}" {{ $typeFilter == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Activ</option>
                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactiv</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        Filtrează
                    </button>
                    <a href="{{ route('templates.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        Resetează
                    </a>
                </div>
            </form>
        </div>

        <!-- Templates Grid -->
        @if($templates->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-lg transition">
                        <!-- Template Header -->
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $template->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $template->contractType->name ?? 'N/A' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $template->is_active ? 'Activ' : 'Inactiv' }}
                                </span>
                            </div>

                            @if($template->description)
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ $template->description }}
                                </p>
                            @endif

                            <!-- Template Stats -->
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Variabile</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ is_array($template->variables) ? count($template->variables) : 0 }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Utilizări</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $template->contracts_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                Creat: {{ $template->created_at->format('d.m.Y') }}
                            </div>
                        </div>

                        <!-- Template Actions -->
                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex items-center justify-between">
                            <a href="{{ route('templates.preview', $template->id) }}"
                               class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                Previzualizează
                            </a>
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('contracts.create', ['template_id' => $template->id]) }}"
                                   class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 font-medium"
                                   title="Folosește șablonul">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('templates.edit', $template->id) }}"
                                   class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 font-medium"
                                   title="Editează">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('templates.destroy', $template->id) }}"
                                      onsubmit="return confirm('Sigur doriți să ștergeți acest șablon?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium"
                                            title="Șterge">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $templates->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Niciun șablon</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $search || $typeFilter || $statusFilter ? 'Nu s-au găsit șabloane cu criteriile selectate.' : 'Începeți prin a crea primul șablon de contract.' }}
                </p>
                <div class="mt-6">
                    @if($search || $typeFilter || $statusFilter)
                        <a href="{{ route('templates.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                            Resetează filtrele
                        </a>
                    @else
                        <a href="{{ route('templates.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Creează primul șablon
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </x-app.container>
</x-layouts.app>
