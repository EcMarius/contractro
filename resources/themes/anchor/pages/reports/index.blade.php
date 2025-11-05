<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Contract;
	use App\Models\Invoice;

	middleware(['auth', 'verified']);
	name('reports.index');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$companyIds = $companies->pluck('id');

	// Get selected company from request
	$selectedCompany = request('company');

	// Calculate stats
	$totalRevenue = Invoice::whereIn('company_id', $companyIds)
		->where('status', 'paid')
		->sum('total_amount');

	$pendingRevenue = Invoice::whereIn('company_id', $companyIds)
		->whereIn('status', ['issued', 'overdue'])
		->sum('total_amount');

	$activeContracts = Contract::whereIn('company_id', $companyIds)
		->where('status', 'active')
		->count();

	$totalInvoices = Invoice::whereIn('company_id', $companyIds)
		->count();
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				Rapoarte Financiare
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				Generează și vizualizează rapoarte financiare detaliate
			</p>
		</div>

		{{-- Stats Overview --}}
		<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Venituri Totale</p>
						<p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($totalRevenue, 0, ',', '.') }} RON</p>
					</div>
					<div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
						<svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">În Așteptare</p>
						<p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($pendingRevenue, 0, ',', '.') }} RON</p>
					</div>
					<div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
						<svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Contracte Active</p>
						<p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $activeContracts }}</p>
					</div>
					<div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
						<svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Facturi</p>
						<p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $totalInvoices }}</p>
					</div>
					<div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
						<svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
						</svg>
					</div>
				</div>
			</div>
		</div>

		{{-- Generate Report --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
				Generează Raport Nou
			</h2>

			<form method="POST" action="{{ route('reports.generate') }}" class="space-y-4">
				@csrf

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
					{{-- Company --}}
					<div>
						<label for="company_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Companie <span class="text-red-500">*</span>
						</label>
						<select name="company_id" id="company_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Selectează compania</option>
							@foreach($companies as $company)
								<option value="{{ $company->id }}" {{ $selectedCompany == $company->id ? 'selected' : '' }}>
									{{ $company->name }}
								</option>
							@endforeach
						</select>
					</div>

					{{-- Report Type --}}
					<div>
						<label for="report_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Tip Raport <span class="text-red-500">*</span>
						</label>
						<select name="report_type" id="report_type" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Selectează tipul</option>
							<option value="revenue">Raport Venituri</option>
							<option value="profitability">Raport Profitabilitate</option>
							<option value="contract_stats">Statistici Contracte</option>
							<option value="client_analysis">Analiză Clienți</option>
						</select>
					</div>

					{{-- Period --}}
					<div>
						<label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Perioadă <span class="text-red-500">*</span>
						</label>
						<select name="period" id="period" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">Selectează perioada</option>
							<option value="this_month">Luna curentă</option>
							<option value="last_month">Luna trecută</option>
							<option value="this_quarter">Trimestrul curent</option>
							<option value="last_quarter">Trimestrul trecut</option>
							<option value="this_year">Anul curent</option>
							<option value="last_year">Anul trecut</option>
							<option value="custom">Perioadă personalizată</option>
						</select>
					</div>
				</div>

				{{-- Custom Date Range (hidden by default) --}}
				<div id="custom-dates" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Data Început
						</label>
						<input type="date" name="start_date" id="start_date"
							class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>

					<div>
						<label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							Data Sfârșit
						</label>
						<input type="date" name="end_date" id="end_date"
							class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>
				</div>

				<div class="flex justify-end">
					<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
						</svg>
						Generează Raport
					</button>
				</div>
			</form>
		</div>

		{{-- Quick Reports --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
				Rapoarte Rapide
			</h2>

			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<a href="{{ route('reports.generate', ['report_type' => 'revenue', 'period' => 'this_month']) }}" class="block p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
					<svg class="w-8 h-8 text-green-600 dark:text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
					</svg>
					<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Venituri Luna Curentă</h3>
					<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Raport detaliat venituri</p>
				</a>

				<a href="{{ route('reports.generate', ['report_type' => 'contract_stats', 'period' => 'this_year']) }}" class="block p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
					<svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
					</svg>
					<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Statistici Contracte</h3>
					<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Anul curent</p>
				</a>

				<a href="{{ route('reports.generate', ['report_type' => 'profitability', 'period' => 'last_quarter']) }}" class="block p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
					<svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
					</svg>
					<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Profitabilitate</h3>
					<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Trimestrul trecut</p>
				</a>

				<a href="{{ route('reports.generate', ['report_type' => 'client_analysis', 'period' => 'this_year']) }}" class="block p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
					<svg class="w-8 h-8 text-orange-600 dark:text-orange-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
					</svg>
					<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Analiză Clienți</h3>
					<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Anul curent</p>
				</a>
			</div>
		</div>

	</x-app.container>

	<script>
		// Show/hide custom date range based on period selection
		document.getElementById('period').addEventListener('change', function() {
			const customDates = document.getElementById('custom-dates');
			if (this.value === 'custom') {
				customDates.classList.remove('hidden');
				document.getElementById('start_date').required = true;
				document.getElementById('end_date').required = true;
			} else {
				customDates.classList.add('hidden');
				document.getElementById('start_date').required = false;
				document.getElementById('end_date').required = false;
			}
		});
	</script>
</x-layouts.app>
