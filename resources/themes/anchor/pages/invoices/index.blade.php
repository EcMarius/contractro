<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Invoice;

	middleware(['auth', 'verified']);
	name('invoices.index');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$companyIds = $companies->pluck('id');

	// Get filters from request
	$selectedCompany = request('company');
	$selectedStatus = request('status');
	$search = request('search');
	$sortBy = request('sort_by', 'created_at');
	$sortDir = request('sort_dir', 'desc');

	// Build query
	$query = Invoice::with('company')
		->whereIn('company_id', $companyIds);

	if ($selectedCompany) {
		$query->where('company_id', $selectedCompany);
	}

	if ($selectedStatus) {
		$query->where('status', $selectedStatus);
	}

	if ($search) {
		$query->where(function($q) use ($search) {
			$q->where('invoice_number', 'like', "%{$search}%")
			  ->orWhere('client_name', 'like', "%{$search}%")
			  ->orWhere('client_cui', 'like', "%{$search}%");
		});
	}

	$query->orderBy($sortBy, $sortDir);

	$invoices = $query->paginate(20)->withQueryString();

	// Status options
	$statusOptions = [
		'draft' => __('invoices.status.draft'),
		'issued' => __('invoices.status.issued'),
		'paid' => __('invoices.status.paid'),
		'overdue' => __('invoices.status.overdue'),
		'cancelled' => __('invoices.status.cancelled'),
	];

	$statusColors = [
		'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'issued' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
		'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
		'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
	];

	// Calculate totals
	$totalAmount = $invoices->sum('total_amount');
	$paidAmount = $invoices->where('status', 'paid')->sum('total_amount');
	$pendingAmount = $invoices->whereIn('status', ['issued', 'overdue'])->sum('total_amount');
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
					{{ __('invoices.invoices') }}
				</h1>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					{{ __('Gestionează toate facturile tale') }}
				</p>
			</div>
			<a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
				<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
				</svg>
				{{ __('invoices.new_invoice') }}
			</a>
		</div>

		{{-- Stats Cards --}}
		<div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('invoices.stats.total_amount') }}</p>
						<p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($totalAmount, 2, ',', '.') }} RON</p>
					</div>
					<div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
						<svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('invoices.stats.paid_amount') }}</p>
						<p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($paidAmount, 2, ',', '.') }} RON</p>
					</div>
					<div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
						<svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('invoices.stats.pending_amount') }}</p>
						<p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($pendingAmount, 2, ',', '.') }} RON</p>
					</div>
					<div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
						<svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>
		</div>

		{{-- Filters --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<form method="GET" class="space-y-4">
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
					{{-- Company Filter --}}
					<div>
						<label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							{{ __('invoices.company') }}
						</label>
						<select name="company" id="company" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">{{ __('invoices.filters.all_companies') }}</option>
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
							{{ __('Status') }}
						</label>
						<select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
							<option value="">{{ __('invoices.filters.all_statuses') }}</option>
							@foreach($statusOptions as $value => $label)
								<option value="{{ $value }}" {{ $selectedStatus == $value ? 'selected' : '' }}>
									{{ $label }}
								</option>
							@endforeach
						</select>
					</div>

					{{-- Search --}}
					<div>
						<label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							{{ __('common.search') }}
						</label>
						<input type="text" name="search" id="search" value="{{ $search }}" placeholder="{{ __('invoices.filters.search_placeholder') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>
				</div>

				<div class="flex items-center justify-between">
					<div class="text-sm text-gray-600 dark:text-gray-400">
						{{ $invoices->total() }} {{ $invoices->total() == 1 ? __('factură găsită') : __('facturi găsite') }}
					</div>
					<div class="flex gap-2">
						<a href="{{ route('invoices.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
							{{ __('invoices.filters.reset') }}
						</a>
						<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
							{{ __('invoices.filters.apply') }}
						</button>
					</div>
				</div>
			</form>
		</div>

		{{-- Invoices Table --}}
		@if($invoices->count() > 0)
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
						<thead class="bg-gray-50 dark:bg-gray-900">
							<tr>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('invoices.index', array_merge(request()->all(), ['sort_by' => 'invoice_number', 'sort_dir' => $sortBy == 'invoice_number' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										{{ __('invoices.invoice_number') }}
										@if($sortBy == 'invoice_number')
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
									{{ __('invoices.company') }}
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									{{ __('Client') }}
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									{{ __('Status') }}
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('invoices.index', array_merge(request()->all(), ['sort_by' => 'issue_date', 'sort_dir' => $sortBy == 'issue_date' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										{{ __('invoices.issue_date') }}
										@if($sortBy == 'issue_date')
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
									{{ __('invoices.due_date') }}
								</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
									<a href="{{ route('invoices.index', array_merge(request()->all(), ['sort_by' => 'total_amount', 'sort_dir' => $sortBy == 'total_amount' && $sortDir == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
										{{ __('invoices.total') }}
										@if($sortBy == 'total_amount')
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
									{{ __('Acțiuni') }}
								</th>
							</tr>
						</thead>
						<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
							@foreach($invoices as $invoice)
								<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
									<td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
										{{ $invoice->invoice_number }}
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										{{ $invoice->company->name }}
									</td>
									<td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
										<div class="font-medium">{{ $invoice->client_name }}</div>
										@if($invoice->client_cui)
											<div class="text-gray-500 dark:text-gray-400 text-xs">CUI: {{ $invoice->client_cui }}</div>
										@endif
									</td>
									<td class="px-4 py-4 whitespace-nowrap">
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$invoice->status] ?? '' }}">
											{{ $statusOptions[$invoice->status] ?? $invoice->status }}
										</span>
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										{{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-' }}
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
										@if($invoice->due_date)
											<span class="{{ $invoice->due_date->isPast() && $invoice->status != 'paid' ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
												{{ $invoice->due_date->format('d.m.Y') }}
											</span>
										@else
											-
										@endif
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
										{{ number_format($invoice->total_amount, 2, ',', '.') }} RON
									</td>
									<td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
										<div class="flex items-center justify-end gap-2">
											<a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="{{ __('common.view') }}">
												<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
												</svg>
											</a>
											<a href="{{ route('invoices.edit', $invoice->id) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400" title="{{ __('common.edit') }}">
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
				@if($invoices->hasPages())
					<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
						{{ $invoices->links() }}
					</div>
				@endif
			</div>
		@else
			{{-- Empty State --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-12 text-center">
				<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
				</svg>
				<h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('invoices.messages.no_invoices') }}</h3>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					@if(request()->hasAny(['company', 'status', 'search']))
						{{ __('Nicio factură nu corespunde filtrelor selectate.') }}
					@else
						{{ __('Începe prin a crea prima ta factură.') }}
					@endif
				</p>
				<div class="mt-6">
					@if(request()->hasAny(['company', 'status', 'search']))
						<a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
							{{ __('Resetează filtrele') }}
						</a>
					@else
						<a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
							</svg>
							{{ __('Creează prima factură') }}
						</a>
					@endif
				</div>
			</div>
		@endif

	</x-app.container>
</x-layouts.app>
