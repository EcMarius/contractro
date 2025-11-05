<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\ContractType;

	middleware(['auth', 'verified']);
	name('contracts.create');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$contractTypes = ContractType::where('is_active', true)->orderBy('name')->get();

	// Pre-select company if passed in query string
	$selectedCompanyId = request('company', old('company_id'));
?>

<x-layouts.app>
	<x-app.container class="max-w-4xl space-y-6">

		{{-- Header --}}
		<div>
			<a href="{{ route('contracts.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
				<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				Înapoi la Contracte
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				Contract Nou
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				Creează un contract nou pentru compania ta
			</p>
		</div>

		{{-- Company Selection Notice --}}
		@if($companies->count() == 0)
			<div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
							Necesită companie
						</h3>
						<div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
							<p>Trebuie să ai cel puțin o companie creată pentru a putea crea contracte.</p>
						</div>
						<div class="mt-4">
							<a href="{{ route('companies.create') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg hover:bg-yellow-200 dark:bg-yellow-800 dark:text-yellow-100 dark:hover:bg-yellow-700">
								Creează o companie
							</a>
						</div>
					</div>
				</div>
			</div>
		@else
			{{-- Form --}}
			<form method="POST" action="{{ route('contracts.store') }}" class="space-y-6">
				@csrf

				{{-- Company & Type Selection --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Companie & Tip Contract
					</h2>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						{{-- Company --}}
						<div>
							<label for="company_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Companie <span class="text-red-500">*</span>
							</label>
							<select name="company_id" id="company_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
								<option value="">Selectează compania</option>
								@foreach($companies as $company)
									<option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
										{{ $company->name }}
									</option>
								@endforeach
							</select>
							@error('company_id')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Contract Type --}}
						<div>
							<label for="contract_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Tip Contract <span class="text-red-500">*</span>
							</label>
							<select name="contract_type_id" id="contract_type_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
								<option value="">Selectează tipul</option>
								@foreach($contractTypes as $type)
									<option value="{{ $type->id }}" {{ old('contract_type_id') == $type->id ? 'selected' : '' }}>
										{{ $type->name }}
									</option>
								@endforeach
							</select>
							@error('contract_type_id')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>
					</div>
				</div>

				{{-- Basic Information --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Informații de Bază
					</h2>

					<div class="space-y-4">
						{{-- Title --}}
						<div>
							<label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Titlu Contract <span class="text-red-500">*</span>
							</label>
							<input type="text" name="title" id="title" required
								value="{{ old('title') }}"
								placeholder="ex: Contract de prestări servicii IT"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('title')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Client Name --}}
						<div>
							<label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Nume Client
							</label>
							<input type="text" name="client_name" id="client_name"
								value="{{ old('client_name') }}"
								placeholder="ex: SC Exemplu SRL"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
								Numele clientului cu care semnezi contractul
							</p>
							@error('client_name')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Description --}}
						<div>
							<label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Descriere
							</label>
							<textarea name="description" id="description" rows="3"
								placeholder="Descriere scurtă a contractului..."
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('description') }}</textarea>
							@error('description')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>
					</div>
				</div>

				{{-- Contract Content --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Conținut Contract
					</h2>

					<div>
						<label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Conținut <span class="text-red-500">*</span>
						</label>
						<textarea name="content" id="content" rows="15" required
							placeholder="Introduceți conținutul complet al contractului aici..."
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 font-mono text-sm">{{ old('content') }}</textarea>
						<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
							Conținutul integral al contractului. Puteți folosi formatare simplă sau HTML.
						</p>
						@error('content')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>

				{{-- Contract Dates & Value --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Date & Valoare
					</h2>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						{{-- Start Date --}}
						<div>
							<label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Data Început <span class="text-red-500">*</span>
							</label>
							<input type="date" name="start_date" id="start_date" required
								value="{{ old('start_date') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('start_date')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- End Date --}}
						<div>
							<label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Data Sfârșit
							</label>
							<input type="date" name="end_date" id="end_date"
								value="{{ old('end_date') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
								Opțional - lasă gol pentru contract pe durată nedeterminată
							</p>
							@error('end_date')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Value --}}
						<div>
							<label for="value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Valoare (RON)
							</label>
							<input type="number" name="value" id="value" step="0.01" min="0"
								value="{{ old('value') }}"
								placeholder="0.00"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('value')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Billing Cycle --}}
						<div>
							<label for="billing_cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Ciclu Facturare
							</label>
							<select name="billing_cycle" id="billing_cycle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
								<option value="">Niciuna</option>
								<option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Lunar</option>
								<option value="quarterly" {{ old('billing_cycle') == 'quarterly' ? 'selected' : '' }}>Trimestrial</option>
								<option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Anual</option>
								<option value="one-time" {{ old('billing_cycle') == 'one-time' ? 'selected' : '' }}>O singură dată</option>
							</select>
							@error('billing_cycle')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>
					</div>

					{{-- Auto Renewal --}}
					<div class="mt-4">
						<label class="flex items-start">
							<input type="checkbox" name="auto_renewal" id="auto_renewal" value="1" {{ old('auto_renewal') ? 'checked' : '' }}
								class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 mt-1">
							<span class="ml-2">
								<span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reînnoire automată</span>
								<span class="block text-xs text-gray-500 dark:text-gray-400">
									Contractul va fi reînnoit automat la expirare
								</span>
							</span>
						</label>
					</div>
				</div>

				{{-- Status --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Status Inițial
					</h2>

					<div>
						<label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Status <span class="text-red-500">*</span>
						</label>
						<select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Ciornă</option>
							<option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>În așteptare semnare</option>
							<option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Activ (fără semnare)</option>
						</select>
						<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
							Ciornă = salvează pentru editare ulterioară | În așteptare = pregătit pentru semnare | Activ = contract activat direct
						</p>
						@error('status')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>

				{{-- Actions --}}
				<div class="flex items-center justify-end gap-3">
					<a href="{{ route('contracts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
						Anulează
					</a>
					<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
						Creează Contractul
					</button>
				</div>
			</form>
		@endif

	</x-app.container>
</x-layouts.app>
