<?php
    use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\Contract;
	use App\Models\Invoice;
	use App\Models\ContractTask;

	middleware(['auth', 'verified']);
    name('dashboard');

	// Get user's data
	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$companyIds = $companies->pluck('id');

	// Get active company or first company
	$activeCompanyId = session('active_company_id') ?? $companies->first()?->id;
	$activeCompany = $companies->firstWhere('id', $activeCompanyId);

	// Get statistics
	$stats = [
		'total_companies' => $companies->count(),
		'total_contracts' => Contract::whereIn('company_id', $companyIds)->count(),
		'active_contracts' => Contract::whereIn('company_id', $companyIds)->where('status', 'active')->count(),
		'pending_contracts' => Contract::whereIn('company_id', $companyIds)->where('status', 'pending')->count(),
		'signed_this_month' => Contract::whereIn('company_id', $companyIds)
			->where('status', 'signed')
			->whereMonth('signed_at', now()->month)
			->count(),
		'expiring_soon' => Contract::whereIn('company_id', $companyIds)
			->where('status', 'active')
			->whereNotNull('end_date')
			->whereBetween('end_date', [now(), now()->addDays(30)])
			->count(),
		'total_invoices' => Invoice::whereIn('company_id', $companyIds)->count(),
		'paid_invoices' => Invoice::whereIn('company_id', $companyIds)->where('status', 'paid')->count(),
		'overdue_invoices' => Invoice::whereIn('company_id', $companyIds)->where('status', 'overdue')->count(),
		'total_revenue' => Invoice::whereIn('company_id', $companyIds)->where('status', 'paid')->sum('total_amount'),
		'pending_revenue' => Invoice::whereIn('company_id', $companyIds)->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
		'my_tasks' => ContractTask::where('assigned_to', $user->id)->where('status', '!=', 'completed')->count(),
	];

	// Get recent contracts
	$recentContracts = Contract::whereIn('company_id', $companyIds)
		->with(['company', 'contractType'])
		->latest()
		->limit(5)
		->get();

	// Get expiring contracts
	$expiringContracts = Contract::whereIn('company_id', $companyIds)
		->where('status', 'active')
		->whereNotNull('end_date')
		->whereBetween('end_date', [now(), now()->addDays(30)])
		->orderBy('end_date')
		->limit(5)
		->get();

	// Get my pending tasks
	$myTasks = ContractTask::where('assigned_to', $user->id)
		->where('status', '!=', 'completed')
		->with('contract')
		->orderBy('due_date')
		->limit(5)
		->get();
?>

<x-layouts.app>
	<x-app.container class="space-y-6" x-cloak>

		{{-- Welcome Header --}}
		<div class="flex flex-col md:flex-row md:items-center md:justify-between">
			<div>
				<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
					Bine ai venit, {{ $user->name }}!
				</h1>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					Aici găsești o privire de ansamblu asupra activității tale
				</p>
			</div>

			<div class="flex gap-3 mt-4 md:mt-0">
				<a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Contract Nou
				</a>
				<a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Factură Nouă
				</a>
			</div>
		</div>

		{{-- Statistics Cards --}}
		<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
			{{-- Total Contracts --}}
			<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
				<div class="p-5">
					<div class="flex items-center">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
							</svg>
						</div>
						<div class="ml-5 w-0 flex-1">
							<dl>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
									Total Contracte
								</dt>
								<dd class="flex items-baseline">
									<div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
										{{ number_format($stats['total_contracts']) }}
									</div>
									<div class="ml-2 text-sm text-gray-600 dark:text-gray-400">
										{{ $stats['active_contracts'] }} active
									</div>
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>

			{{-- Revenue --}}
			<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
				<div class="p-5">
					<div class="flex items-center">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
						</div>
						<div class="ml-5 w-0 flex-1">
							<dl>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
									Venituri Totale
								</dt>
								<dd class="flex items-baseline">
									<div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
										{{ number_format($stats['total_revenue'], 2) }} RON
									</div>
								</dd>
								<dd class="text-sm text-gray-600 dark:text-gray-400">
									{{ number_format($stats['pending_revenue'], 2) }} RON în așteptare
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>

			{{-- Invoices --}}
			<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
				<div class="p-5">
					<div class="flex items-center">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
							</svg>
						</div>
						<div class="ml-5 w-0 flex-1">
							<dl>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
									Facturi
								</dt>
								<dd class="flex items-baseline">
									<div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
										{{ number_format($stats['total_invoices']) }}
									</div>
								</dd>
								<dd class="text-sm">
									<span class="text-green-600 dark:text-green-400">{{ $stats['paid_invoices'] }} plătite</span>
									@if($stats['overdue_invoices'] > 0)
										<span class="ml-2 text-red-600 dark:text-red-400">{{ $stats['overdue_invoices'] }} restante</span>
									@endif
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>

			{{-- Tasks --}}
			<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
				<div class="p-5">
					<div class="flex items-center">
						<div class="flex-shrink-0">
							<svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
							</svg>
						</div>
						<div class="ml-5 w-0 flex-1">
							<dl>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
									Sarcini Pendinte
								</dt>
								<dd class="flex items-baseline">
									<div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
										{{ number_format($stats['my_tasks']) }}
									</div>
								</dd>
								<dd class="text-sm text-gray-600 dark:text-gray-400">
									<a href="{{ route('tasks.my-tasks') }}" class="hover:underline">Vezi toate sarcinile</a>
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>
		</div>

		{{-- Main Content Grid --}}
		<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

			{{-- Recent Contracts --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
				<div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
					<div class="flex items-center justify-between">
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
							Contracte Recente
						</h2>
						<a href="{{ route('contracts.index') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
							Vezi toate →
						</a>
					</div>
				</div>
				<div class="divide-y divide-gray-200 dark:divide-gray-700">
					@forelse($recentContracts as $contract)
						<a href="{{ route('contracts.show', $contract) }}" class="block px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
							<div class="flex items-center justify-between">
								<div class="flex-1 min-w-0">
									<p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
										{{ $contract->title }}
									</p>
									<p class="text-sm text-gray-500 dark:text-gray-400">
										{{ $contract->contract_number }} • {{ $contract->company->name }}
									</p>
								</div>
								<div class="ml-4 flex-shrink-0">
									@php
										$statusColors = [
											'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
											'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
											'signed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
											'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
											'expired' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
											'terminated' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
										];
									@endphp
									<span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full {{ $statusColors[$contract->status] ?? $statusColors['draft'] }}">
										{{ $contract->status_label }}
									</span>
								</div>
							</div>
						</a>
					@empty
						<div class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
							<p>Nu există contracte încă.</p>
							<a href="{{ route('contracts.create') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm">
								Creează primul tău contract →
							</a>
						</div>
					@endforelse
				</div>
			</div>

			{{-- Expiring Contracts Alert --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
				<div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						Contracte ce Expiră (30 zile)
					</h2>
				</div>
				<div class="divide-y divide-gray-200 dark:divide-gray-700">
					@forelse($expiringContracts as $contract)
						<a href="{{ route('contracts.show', $contract) }}" class="block px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
							<div class="flex items-center justify-between">
								<div class="flex-1 min-w-0">
									<p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
										{{ $contract->title }}
									</p>
									<p class="text-sm text-gray-500 dark:text-gray-400">
										{{ $contract->contract_number }}
									</p>
								</div>
								<div class="ml-4 flex-shrink-0 text-right">
									<p class="text-sm font-medium text-red-600 dark:text-red-400">
										{{ $contract->end_date->format('d.m.Y') }}
									</p>
									<p class="text-xs text-gray-500 dark:text-gray-400">
										{{ $contract->end_date->diffForHumans() }}
									</p>
								</div>
							</div>
						</a>
					@empty
						<div class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
							<p>Nu există contracte care expiră în următoarele 30 de zile.</p>
						</div>
					@endforelse
				</div>
			</div>

		</div>

		{{-- My Tasks --}}
		@if($myTasks->count() > 0)
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
			<div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
				<div class="flex items-center justify-between">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						Sarcinile Mele
					</h2>
					<a href="{{ route('tasks.my-tasks') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
						Vezi toate →
					</a>
				</div>
			</div>
			<div class="divide-y divide-gray-200 dark:divide-gray-700">
				@foreach($myTasks as $task)
					<div class="px-5 py-4">
						<div class="flex items-start justify-between">
							<div class="flex-1 min-w-0">
								<p class="text-sm font-medium text-gray-900 dark:text-gray-100">
									{{ $task->title }}
								</p>
								<p class="text-sm text-gray-500 dark:text-gray-400">
									Contract: <a href="{{ route('contracts.show', $task->contract) }}" class="hover:underline">{{ $task->contract->contract_number }}</a>
								</p>
							</div>
							<div class="ml-4 flex-shrink-0">
								@php
									$priorityColors = [
										'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
										'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
										'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
									];
								@endphp
								<span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full {{ $priorityColors[$task->priority] ?? $priorityColors['low'] }}">
									{{ ucfirst($task->priority) }}
								</span>
								@if($task->due_date)
									<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
										Scadență: {{ $task->due_date->format('d.m.Y') }}
									</p>
								@endif
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
		@endif

	</x-app.container>
</x-layouts.app>
