<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Contract;
	use App\Models\ContractType;

	middleware(['auth', 'verified']);
	name('contracts.index');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$companyIds = $companies->pluck('id');

	// Get filters from request
	$selectedCompany = request('company');
	$selectedStatus = request('status');
	$selectedType = request('type');
	$search = request('search');
	$sortBy = request('sort_by', 'created_at');
	$sortDir = request('sort_dir', 'desc');

	// Build query
	$query = Contract::with(['company', 'contractType'])
		->whereIn('company_id', $companyIds);

	if ($selectedCompany) {
		$query->where('company_id', $selectedCompany);
	}

	if ($selectedStatus) {
		$query->where('status', $selectedStatus);
	}

	if ($selectedType) {
		$query->where('contract_type_id', $selectedType);
	}

	if ($search) {
		$query->where(function($q) use ($search) {
			$q->where('contract_number', 'like', "%{$search}%")
			  ->orWhere('title', 'like', "%{$search}%")
			  ->orWhere('client_name', 'like', "%{$search}%");
		});
	}

	$query->orderBy($sortBy, $sortDir);

	$contracts = $query->paginate(20)->withQueryString();

	// Get contract types for filter
	$contractTypes = ContractType::where('is_active', true)->get();

	// Status options
	$statusOptions = [
		'draft' => 'Ciornă',
		'pending' => 'În așteptare',
		'signed' => 'Semnat',
		'active' => 'Activ',
		'expired' => 'Expirat',
		'terminated' => 'Reziliat',
	];

	$statusColors = [
		'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
		'signed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
		'expired' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
		'terminated' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
	];
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
					Contracte
				</h1>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					Gestionează toate contractele tale
				</p>
			</div>
			<a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
				<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
				</svg>
				Contract Nou
			</a>
		</div>

		{{-- Filters --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<form method="GET" class="space-y-4">
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
					{{-- Company Filter --}}
					<div>
						<label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Companie
						</label>
						<select name="company" id="company" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Toate companiile</option>
							@foreach($companies as $company)
								<option value="{{ $company->id }}" {{ $selectedCompany == $company->id ? 'selected' : '' }}>
									{{ $company->name }}
								</option>
							@endforeach
						</select>
					</div>

					{{-- Status Filter --}}
					<div>
						<label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Status
						</label>
						<select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Toate statusurile</option>
							@foreach($statusOptions as $value => $label)
								<option value="{{ $value }}" {{ $selectedStatus == $value ? 'selected' : '' }}>
									{{ $label }}
								</option>
							@endforeach
						</select>
					</div>

					{{-- Type Filter --}}
					<div>
						<label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Tip Contract
						</label>
						<select name="type" id="type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Toate tipurile</option>
							@foreach($contractTypes as $type)
								<option value="{{ $type->id }}" {{ $selectedType == $type->id ? 'selected' : '' }}>
									{{ $type->name }}
								</option>
							@endforeach
						</select>
					</div>

					{{-- Search --}}
					<div>
						<label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Căutare
						</label>
						<input type="text" name="search" id="search" value="{{ $search }}" placeholder="Nr. contract, titlu, client..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>
				</div>

				<div class="flex items-center justify-between">
					<div class="text-sm text-gray-600 dark:text-gray-400">
						{{ $contracts->total() }} {{ $contracts->total() == 1 ? 'contract găsit' : 'contracte găsite' }}
					</div>
					<div class="flex gap-2">
						<a href="{{ route('contracts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
							Resetează
						</a>
						<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
							Filtrează
						</button>
					</div>
				</div>
			</form>
		</div>

		{{-- Contracts Table --}}
		@if($contracts->count() > 0)
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
						<thead class="bg-gray-50 dark:bg-gray-900">
							<tr>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('contracts.index', array_merge(request()->all(), ['sort_by' => 'contract_number', 'sort_dir' => $sortBy == 'contract_number' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										Nr. Contract
										@if($sortBy == 'contract_number')
											<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												@if($sortDir == 'asc')
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
												@else
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
												@endif
											</svg>
										@endif
									</a>
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									Companie
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									Titlu / Client
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									Tip
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									Status
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('contracts.index', array_merge(request()->all(), ['sort_by' => 'start_date', 'sort_dir' => $sortBy == 'start_date' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										Data Început
										@if($sortBy == 'start_date')
											<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												@if($sortDir == 'asc')
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
												@else
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
												@endif
											</svg>
										@endif
									</a>
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('contracts.index', array_merge(request()->all(), ['sort_by' => 'value', 'sort_dir' => $sortBy == 'value' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										Valoare
										@if($sortBy == 'value')
											<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												@if($sortDir == 'asc')
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
												@else
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
												@endif
											</svg>
										@endif
									</a>
								</th>
								<th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									Acțiuni
								</th>
							</tr>
						</thead>
						<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
							@foreach($contracts as $contract)
								<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
									<td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
										{{ $contract->contract_number }}
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										{{ $contract->company->name }}
									</td>
									<td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
										<div class="font-medium">{{ $contract->title }}</div>
										@if($contract->client_name)
											<div class="text-gray-500 dark:text-gray-400 text-xs">{{ $contract->client_name }}</div>
										@endif
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										{{ $contract->contractType->name ?? '-' }}
									</td>
									<td class="px-4 py-4 whitespace-nowrap">
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$contract->status] ?? '' }}">
											{{ $statusOptions[$contract->status] ?? $contract->status }}
										</span>
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										{{ $contract->start_date ? $contract->start_date->format('d.m.Y') : '-' }}
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										@if($contract->value)
											{{ number_format($contract->value, 0, ',', '.') }} RON
										@else
											-
										@endif
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
										<div class="flex items-center justify-end gap-2">
											<a href="{{ route('contracts.show', $contract->id) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="Vezi">
												<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
												</svg>
											</a>
											<a href="{{ route('contracts.edit', $contract->id) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400" title="Editează">
												<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
												</svg>
											</a>
										</div>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>

				{{-- Pagination --}}
				@if($contracts->hasPages())
					<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
						{{ $contracts->links() }}
					</div>
				@endif
			</div>
		@else
			{{-- Empty State --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-12 text-center">
				<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
				</svg>
				<h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nu există contracte</h3>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					@if(request()->hasAny(['company', 'status', 'type', 'search']))
						Niciun contract nu corespunde filtrelor selectate.
					@else
						Începe prin a crea primul tău contract.
					@endif
				</p>
				<div class="mt-6">
					@if(request()->hasAny(['company', 'status', 'type', 'search']))
						<a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
							Resetează filtrele
						</a>
					@else
						<a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
							</svg>
							Creează primul contract
						</a>
					@endif
				</div>
			</div>
		@endif

	</x-app.container>
</x-layouts.app>
