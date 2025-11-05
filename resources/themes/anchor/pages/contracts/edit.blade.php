<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Contract;
	use App\Models\Company;
	use App\Models\ContractType;

	middleware(['auth', 'verified']);
	name('contracts.edit');

	$contractId = request()->route('id');
	$contract = Contract::with(['company', 'contractType'])
		->where('id', $contractId)
		->whereHas('company', function($q) {
			$q->where('user_id', auth()->id());
		})
		->firstOrFail();

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$contractTypes = ContractType::where('is_active', true)->orderBy('name')->get();
?>

<x-layouts.app>
	<x-app.container class="max-w-4xl space-y-6">

		{{-- Header --}}
		<div>
			<a href="{{ route('contracts.show', $contract->id) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
				<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				Înapoi la Contract
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				Editează Contract
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				{{ $contract->contract_number }} • {{ $contract->title }}
			</p>
		</div>

		{{-- Form --}}
		<form method="POST" action="{{ route('contracts.update', $contract->id) }}" class="space-y-6">
			@csrf
			@method('PUT')

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
								<option value="{{ $company->id }}" {{ old('company_id', $contract->company_id) == $company->id ? 'selected' : '' }}>
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
								<option value="{{ $type->id }}" {{ old('contract_type_id', $contract->contract_type_id) == $type->id ? 'selected' : '' }}>
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
							value="{{ old('title', $contract->title) }}"
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
							value="{{ old('client_name', $contract->client_name) }}"
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
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('description', $contract->description) }}</textarea>
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
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 font-mono text-sm">{{ old('content', $contract->content) }}</textarea>
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
							value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}"
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
							value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}"
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
							value="{{ old('value', $contract->value) }}"
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
							<option value="monthly" {{ old('billing_cycle', $contract->billing_cycle) == 'monthly' ? 'selected' : '' }}>Lunar</option>
							<option value="quarterly" {{ old('billing_cycle', $contract->billing_cycle) == 'quarterly' ? 'selected' : '' }}>Trimestrial</option>
							<option value="yearly" {{ old('billing_cycle', $contract->billing_cycle) == 'yearly' ? 'selected' : '' }}>Anual</option>
							<option value="one-time" {{ old('billing_cycle', $contract->billing_cycle) == 'one-time' ? 'selected' : '' }}>O singură dată</option>
						</select>
						@error('billing_cycle')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>

				{{-- Auto Renewal --}}
				<div class="mt-4">
					<label class="flex items-start">
						<input type="checkbox" name="auto_renewal" id="auto_renewal" value="1" {{ old('auto_renewal', $contract->auto_renewal) ? 'checked' : '' }}
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
					Status
				</h2>

				<div>
					<label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
						Status <span class="text-red-500">*</span>
					</label>
					<select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						<option value="draft" {{ old('status', $contract->status) == 'draft' ? 'selected' : '' }}>Ciornă</option>
						<option value="pending" {{ old('status', $contract->status) == 'pending' ? 'selected' : '' }}>În așteptare semnare</option>
						<option value="signed" {{ old('status', $contract->status) == 'signed' ? 'selected' : '' }}>Semnat</option>
						<option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Activ</option>
						<option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Expirat</option>
						<option value="terminated" {{ old('status', $contract->status) == 'terminated' ? 'selected' : '' }}>Reziliat</option>
					</select>
					@error('status')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
					@enderror
				</div>
			</div>

			{{-- Actions --}}
			<div class="flex items-center justify-end gap-3">
				<a href="{{ route('contracts.show', $contract->id) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					Anulează
				</a>
				<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
					Salvează Modificările
				</button>
			</div>
		</form>

	</x-app.container>
</x-layouts.app>
