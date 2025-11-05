<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Contract;

	middleware(['auth', 'verified']);
	name('invoices.create');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();

	// Pre-select company if passed in query string
	$selectedCompanyId = request('company', old('company_id'));

	// Get contracts for selected company or from contract parameter
	$selectedContractId = request('contract', old('contract_id'));
	$contracts = collect();
	if ($selectedCompanyId) {
		$contracts = Contract::where('company_id', $selectedCompanyId)
			->where('status', 'active')
			->orderBy('created_at', 'desc')
			->get();
	}
?>

<x-layouts.app>
	<x-app.container class="max-w-5xl space-y-6">

		{{-- Header --}}
		<div>
			<a href="{{ route('invoices.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
				<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				Înapoi la Facturi
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				Factură Nouă
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				Creează o factură nouă pentru compania ta
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
							<p>Trebuie să ai cel puțin o companie creată pentru a putea crea facturi.</p>
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
			<form method="POST" action="{{ route('invoices.store') }}" class="space-y-6" x-data="invoiceForm()">
				@csrf

				{{-- Company & Contract --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Companie & Contract
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

						{{-- Contract (Optional) --}}
						<div>
							<label for="contract_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Contract (opțional)
							</label>
							<select name="contract_id" id="contract_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
								<option value="">Fără contract asociat</option>
								@foreach($contracts as $contract)
									<option value="{{ $contract->id }}" {{ $selectedContractId == $contract->id ? 'selected' : '' }}>
										{{ $contract->contract_number }} - {{ $contract->title }}
									</option>
								@endforeach
							</select>
							@error('contract_id')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>
					</div>
				</div>

				{{-- Client Information --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Informații Client
					</h2>

					<div class="space-y-4">
						{{-- Client Name --}}
						<div>
							<label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Nume Client <span class="text-red-500">*</span>
							</label>
							<input type="text" name="client_name" id="client_name" required
								value="{{ old('client_name') }}"
								placeholder="SC Exemplu SRL"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('client_name')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
							{{-- Client CUI --}}
							<div>
								<label for="client_cui" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
									CUI Client
								</label>
								<input type="text" name="client_cui" id="client_cui"
									value="{{ old('client_cui') }}"
									placeholder="RO12345678"
									class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							</div>

							{{-- Client Reg Com --}}
							<div>
								<label for="client_reg_com" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
									Nr. Reg. Com. Client
								</label>
								<input type="text" name="client_reg_com" id="client_reg_com"
									value="{{ old('client_reg_com') }}"
									placeholder="J40/1234/2023"
									class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							</div>
						</div>

						{{-- Client Address --}}
						<div>
							<label for="client_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Adresă Client
							</label>
							<textarea name="client_address" id="client_address" rows="2"
								placeholder="Adresa completă a clientului"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('client_address') }}</textarea>
						</div>
					</div>
				</div>

				{{-- Invoice Dates --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Date Factură
					</h2>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						{{-- Issue Date --}}
						<div>
							<label for="issue_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Data Emitere <span class="text-red-500">*</span>
							</label>
							<input type="date" name="issue_date" id="issue_date" required
								value="{{ old('issue_date', date('Y-m-d')) }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('issue_date')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>

						{{-- Due Date --}}
						<div>
							<label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Data Scadență <span class="text-red-500">*</span>
							</label>
							<input type="date" name="due_date" id="due_date" required
								value="{{ old('due_date') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							@error('due_date')
								<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
							@enderror
						</div>
					</div>
				</div>

				{{-- Line Items --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<div class="flex items-center justify-between mb-4">
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
							Produse / Servicii
						</h2>
						<button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
							<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
							</svg>
							Adaugă Produs/Serviciu
						</button>
					</div>

					<div class="space-y-3">
						<template x-for="(item, index) in items" :key="index">
							<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
								<div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
									{{-- Description --}}
									<div class="sm:col-span-5">
										<input type="text" :name="'items[' + index + '][description]'" x-model="item.description" required
											placeholder="Descriere produs/serviciu"
											class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
									</div>

									{{-- Quantity --}}
									<div class="sm:col-span-2">
										<input type="number" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required min="1" step="0.01"
											placeholder="Cantitate"
											@input="calculateTotals()"
											class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
									</div>

									{{-- Unit Price --}}
									<div class="sm:col-span-2">
										<input type="number" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required min="0" step="0.01"
											placeholder="Preț unitar"
											@input="calculateTotals()"
											class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
									</div>

									{{-- Total --}}
									<div class="sm:col-span-2">
										<input type="text" x-model="(item.quantity * item.unit_price).toFixed(2) + ' RON'" readonly
											class="block w-full rounded-md border-gray-300 bg-gray-50 dark:bg-gray-900 shadow-sm dark:text-gray-100 text-sm">
									</div>

									{{-- Remove Button --}}
									<div class="sm:col-span-1 flex items-center justify-center">
										<button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800 dark:text-red-400">
											<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
											</svg>
										</button>
									</div>
								</div>
							</div>
						</template>
					</div>

					{{-- Totals --}}
					<div class="mt-6 flex justify-end">
						<div class="w-full sm:w-80 space-y-2">
							<div class="flex justify-between text-sm">
								<span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
								<span class="font-medium text-gray-900 dark:text-gray-100" x-text="subtotal.toFixed(2) + ' RON'"></span>
							</div>
							<div class="flex justify-between text-sm">
								<span class="text-gray-600 dark:text-gray-400">TVA (19%):</span>
								<span class="font-medium text-gray-900 dark:text-gray-100" x-text="vat.toFixed(2) + ' RON'"></span>
							</div>
							<div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
								<span class="text-gray-900 dark:text-gray-100">Total:</span>
								<span class="text-blue-600 dark:text-blue-400" x-text="total.toFixed(2) + ' RON'"></span>
							</div>
						</div>
					</div>

					{{-- Hidden fields for totals --}}
					<input type="hidden" name="subtotal_amount" x-model="subtotal">
					<input type="hidden" name="vat_amount" x-model="vat">
					<input type="hidden" name="total_amount" x-model="total">
				</div>

				{{-- Notes --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Observații
					</h2>
					<textarea name="notes" id="notes" rows="3"
						placeholder="Note sau observații suplimentare (opțional)"
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('notes') }}</textarea>
				</div>

				{{-- Actions --}}
				<div class="flex items-center justify-end gap-3">
					<a href="{{ route('invoices.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
						Anulează
					</a>
					<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
						Creează Factura
					</button>
				</div>
			</form>
		@endif

	</x-app.container>

	<script>
		function invoiceForm() {
			return {
				items: [
					{ description: '', quantity: 1, unit_price: 0 }
				],
				subtotal: 0,
				vat: 0,
				total: 0,

				addItem() {
					this.items.push({ description: '', quantity: 1, unit_price: 0 });
				},

				removeItem(index) {
					if (this.items.length > 1) {
						this.items.splice(index, 1);
						this.calculateTotals();
					}
				},

				calculateTotals() {
					this.subtotal = this.items.reduce((sum, item) => {
						return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
					}, 0);

					this.vat = this.subtotal * 0.19; // 19% VAT
					this.total = this.subtotal + this.vat;
				},

				init() {
					this.calculateTotals();
				}
			}
		}
	</script>
</x-layouts.app>
