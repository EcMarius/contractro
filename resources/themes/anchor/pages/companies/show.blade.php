<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Contract;
	use App\Models\Invoice;

	middleware(['auth', 'verified']);
	name('companies.show');

	$companyId = request()->route('id');
	$company = Company::where('id', $companyId)
		->where('user_id', auth()->id())
		->firstOrFail();

	// Get statistics
	$totalContracts = Contract::where('company_id', $company->id)->count();
	$activeContracts = Contract::where('company_id', $company->id)->where('status', 'active')->count();
	$pendingContracts = Contract::where('company_id', $company->id)->where('status', 'pending')->count();

	$totalInvoices = Invoice::where('company_id', $company->id)->count();
	$paidInvoices = Invoice::where('company_id', $company->id)->where('status', 'paid')->count();
	$overdueInvoices = Invoice::where('company_id', $company->id)->where('status', 'overdue')->count();

	$totalRevenue = Invoice::where('company_id', $company->id)->where('status', 'paid')->sum('total_amount');
	$pendingRevenue = Invoice::where('company_id', $company->id)->whereIn('status', ['issued', 'overdue'])->sum('total_amount');

	// Get recent contracts and invoices
	$recentContracts = Contract::where('company_id', $company->id)
		->with('contractType')
		->latest()
		->limit(5)
		->get();

	$recentInvoices = Invoice::where('company_id', $company->id)
		->latest()
		->limit(5)
		->get();
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div class="flex items-start justify-between">
			<div>
				<a href="{{ route('companies.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
					</svg>
					{{ __('common.back') }} {{ __('companies.companies') }}
				</a>
				<div class="flex items-center gap-4">
					@if($company->logo_path)
						<img src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }}" class="w-16 h-16 rounded-lg object-contain bg-white dark:bg-gray-800 p-2">
					@else
						<div class="w-16 h-16 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
							<svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
							</svg>
						</div>
					@endif
					<div>
						<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $company->name }}</h1>
						@if($company->cui)
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('companies.cui') }}: {{ $company->cui }}</p>
						@endif
					</div>
				</div>
			</div>
			<div class="flex gap-2">
				<a href="{{ route('companies.edit', $company->id) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
					</svg>
					{{ __('common.edit') }}
				</a>
				<form method="POST" action="{{ route('companies.destroy', $company->id) }}" onsubmit="return confirm('{{ __('companies.messages.confirm_delete') }}');">
					@csrf
					@method('DELETE')
					<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
						</svg>
						{{ __('common.delete') }}
					</button>
				</form>
			</div>
		</div>

		{{-- Statistics Cards --}}
		<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
			{{-- Total Contracts --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('companies.stats.total_contracts') }}</p>
						<p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $totalContracts }}</p>
						<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $activeContracts }} {{ __('contracts.statuses.active') }}, {{ $pendingContracts }} {{ __('common.pending') }}</p>
					</div>
					<div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
						<svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
						</svg>
					</div>
				</div>
			</div>

			{{-- Total Revenue --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('companies.stats.total_revenue') }}</p>
						<p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($totalRevenue, 0, ',', '.') }} RON</p>
						<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($pendingRevenue, 0, ',', '.') }} RON {{ __('companies.stats.pending_revenue') }}</p>
					</div>
					<div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
						<svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			{{-- Total Invoices --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('companies.stats.total_invoices') }}</p>
						<p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $totalInvoices }}</p>
						<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $paidInvoices }} {{ __('invoices.statuses.paid') }}, {{ $overdueInvoices }} {{ __('invoices.statuses.overdue') }}</p>
					</div>
					<div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
						<svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			{{-- Quick Action --}}
			<div class="bg-gradient-to-br from-blue-500 to-blue-600 shadow-sm rounded-lg p-6 text-white">
				<p class="text-sm font-medium opacity-90">{{ __('companies.quick_actions') }}</p>
				<div class="mt-4 space-y-2">
					<a href="{{ route('contracts.create', ['company' => $company->id]) }}" class="block w-full px-3 py-2 text-sm font-medium text-blue-600 bg-white rounded-lg hover:bg-gray-50 text-center">
						+ {{ __('companies.new_contract') }}
					</a>
					<a href="{{ route('invoices.create', ['company' => $company->id]) }}" class="block w-full px-3 py-2 text-sm font-medium text-blue-600 bg-white rounded-lg hover:bg-gray-50 text-center">
						+ {{ __('companies.new_invoice') }}
					</a>
				</div>
			</div>
		</div>

		{{-- Company Details --}}
		<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
			{{-- Company Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('companies.company_information') }}</h2>
				<dl class="space-y-3">
					@if($company->cui)
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.cui') }}</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100">{{ $company->cui }}</dd>
						</div>
					@endif
					@if($company->reg_com)
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.reg_com') }}</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100">{{ $company->reg_com }}</dd>
						</div>
					@endif
					@if($company->address)
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.address') }}</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100">{{ $company->address }}</dd>
						</div>
					@endif
					@if($company->city || $company->county)
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.city') }} / {{ __('companies.county') }}</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100">
								{{ $company->city }}@if($company->city && $company->county), @endif{{ $company->county }}
							</dd>
						</div>
					@endif
				</dl>
			</div>

			{{-- Contact & Banking Information --}}
			<div class="space-y-6">
				{{-- Contact Information --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('companies.contact_information') }}</h2>
					<dl class="space-y-3">
						@if($company->email)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.email') }}</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100">
									<a href="mailto:{{ $company->email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $company->email }}</a>
								</dd>
							</div>
						@endif
						@if($company->phone)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.phone') }}</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100">
									<a href="tel:{{ $company->phone }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $company->phone }}</a>
								</dd>
							</div>
						@endif
						@if($company->website)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.website') }}</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100">
									<a href="{{ $company->website }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $company->website }}</a>
								</dd>
							</div>
						@endif
					</dl>
				</div>

				{{-- Banking Information --}}
				@if($company->bank_name || $company->iban)
					<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('companies.banking_information') }}</h2>
						<dl class="space-y-3">
							@if($company->bank_name)
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.bank_name') }}</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100">{{ $company->bank_name }}</dd>
								</div>
							@endif
							@if($company->iban)
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('companies.iban') }}</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $company->iban }}</dd>
								</div>
							@endif
						</dl>
					</div>
				@endif
			</div>
		</div>

		{{-- Recent Contracts --}}
		@if($recentContracts->count() > 0)
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between mb-4">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('contracts.recent_contracts') }}</h2>
					<a href="{{ route('contracts.index', ['company' => $company->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
						{{ __('common.view_all') }} →
					</a>
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
						<thead>
							<tr>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('contracts.contract_number') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('contracts.type') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('contracts.status') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('contracts.value') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('common.date') }}</th>
								<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('common.actions') }}</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 dark:divide-gray-700">
							@foreach($recentContracts as $contract)
								<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $contract->contract_number }}</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $contract->contractType->name ?? '-' }}</td>
									<td class="px-4 py-3">
										@php
											$statusColors = [
												'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
												'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
												'signed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
												'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
												'expired' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
												'terminated' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
											];
											$statusLabels = [
												'draft' => 'Ciornă',
												'pending' => 'În așteptare',
												'signed' => 'Semnat',
												'active' => 'Activ',
												'expired' => 'Expirat',
												'terminated' => 'Reziliat',
											];
										@endphp
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$contract->status] ?? '' }}">
											{{ $statusLabels[$contract->status] ?? $contract->status }}
										</span>
									</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
										@if($contract->value)
											{{ number_format($contract->value, 0, ',', '.') }} RON
										@else
											-
										@endif
									</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $contract->start_date ? $contract->start_date->format('d.m.Y') : '-' }}</td>
									<td class="px-4 py-3 text-right text-sm">
										<a href="{{ route('contracts.show', $contract->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
											{{ __('common.view') }}
										</a>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		@endif

		{{-- Recent Invoices --}}
		@if($recentInvoices->count() > 0)
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between mb-4">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('invoices.recent_invoices') }}</h2>
					<a href="{{ route('invoices.index', ['company' => $company->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
						{{ __('common.view_all') }} →
					</a>
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
						<thead>
							<tr>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('invoices.invoice_number') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('invoices.client') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('invoices.status') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('invoices.total') }}</th>
								<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('common.date') }}</th>
								<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('common.actions') }}</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 dark:divide-gray-700">
							@foreach($recentInvoices as $invoice)
								<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->client_name }}</td>
									<td class="px-4 py-3">
										@php
											$statusColors = [
												'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
												'issued' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
												'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
												'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
												'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
											];
											$statusLabels = [
												'draft' => 'Ciornă',
												'issued' => 'Emisă',
												'paid' => 'Plătită',
												'overdue' => 'Restantă',
												'cancelled' => 'Anulată',
											];
										@endphp
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$invoice->status] ?? '' }}">
											{{ $statusLabels[$invoice->status] ?? $invoice->status }}
										</span>
									</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ number_format($invoice->total_amount, 2, ',', '.') }} RON</td>
									<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-' }}</td>
									<td class="px-4 py-3 text-right text-sm">
										<a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
											{{ __('common.view') }}
										</a>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		@endif

	</x-app.container>
</x-layouts.app>
