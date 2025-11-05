<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;
	use App\Models\ContractTask;

	middleware(['auth', 'verified']);
	name('tasks.index');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)->get();
	$companyIds = $companies->pluck('id');

	// Get filters
	$selectedStatus = request('status');
	$showOverdue = request('overdue');

	// Build query for tasks
	$query = ContractTask::with(['contract.company', 'assignedUser'])
		->whereHas('contract', function($q) use ($companyIds) {
			$q->whereIn('company_id', $companyIds);
		})
		->where(function($q) use ($user) {
			$q->where('assigned_to', $user->id)
			  ->orWhereHas('contract.company', function($q2) use ($user) {
				  $q2->where('user_id', $user->id);
			  });
		});

	if ($selectedStatus) {
		$query->where('status', $selectedStatus);
	}

	if ($showOverdue) {
		$query->where('due_date', '<', now())
			  ->whereNotIn('status', ['completed']);
	}

	$tasks = $query->orderBy('due_date', 'asc')
				   ->orderBy('priority', 'desc')
				   ->get();

	// Group tasks by status
	$tasksByStatus = $tasks->groupBy('status');

	// Count stats
	$totalTasks = $tasks->count();
	$pendingTasks = $tasks->where('status', 'pending')->count();
	$inProgressTasks = $tasks->where('status', 'in_progress')->count();
	$completedTasks = $tasks->where('status', 'completed')->count();
	$overdueTasks = $tasks->filter(fn($task) => $task->due_date && $task->due_date->isPast() && $task->status != 'completed')->count();

	// Status colors
	$statusColors = [
		'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
	];

	$priorityColors = [
		'low' => 'text-gray-500',
		'medium' => 'text-yellow-500',
		'high' => 'text-red-500',
	];
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div class="flex items-center justify-between">
			<div>
				<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
					{{ __('contracts.tasks') }}
				</h1>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					{{ __('common.manage_your_tasks') }}
				</p>
			</div>
		</div>

		{{-- Stats Cards --}}
		<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('common.total') }}</p>
						<p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $totalTasks }}</p>
					</div>
					<div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
						<svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('common.pending') }}</p>
						<p class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1">{{ $pendingTasks }}</p>
					</div>
					<div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
						<svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('contracts.task_status.in_progress') }}</p>
						<p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $inProgressTasks }}</p>
					</div>
					<div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
						<svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
						</svg>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('common.completed') }}</p>
						<p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $completedTasks }}</p>
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
						<p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('common.overdue') }}</p>
						<p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $overdueTasks }}</p>
					</div>
					<div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
						<svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
						</svg>
					</div>
				</div>
			</div>
		</div>

		{{-- Filters --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<form method="GET" class="flex gap-4 items-end">
				<div class="flex-1">
					<label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
						{{ __('contracts.status_label') }}
					</label>
					<select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						<option value="">{{ __('contracts.filters.all_statuses') }}</option>
						<option value="pending" {{ $selectedStatus == 'pending' ? 'selected' : '' }}>{{ __('contracts.task_status.pending') }}</option>
						<option value="in_progress" {{ $selectedStatus == 'in_progress' ? 'selected' : '' }}>{{ __('contracts.task_status.in_progress') }}</option>
						<option value="completed" {{ $selectedStatus == 'completed' ? 'selected' : '' }}>{{ __('contracts.task_status.completed') }}</option>
					</select>
				</div>

				<div class="flex items-center">
					<label class="flex items-center">
						<input type="checkbox" name="overdue" value="1" {{ $showOverdue ? 'checked' : '' }}
							class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
						<span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('common.only_overdue') }}</span>
					</label>
				</div>

				<div class="flex gap-2">
					<a href="{{ route('tasks.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
						{{ __('common.reset') }}
					</a>
					<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
						{{ __('common.filter') }}
					</button>
				</div>
			</form>
		</div>

		{{-- Tasks List --}}
		@if($tasks->count() > 0)
			<div class="space-y-4">
				@foreach($tasks as $task)
					@php
						$isOverdue = $task->due_date && $task->due_date->isPast() && $task->status != 'completed';
					@endphp
					<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 {{ $isOverdue ? 'border-l-4 border-red-500' : '' }}">
						<div class="flex items-start justify-between">
							<div class="flex-1">
								<div class="flex items-center gap-2 mb-2">
									<h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $task->title }}</h3>
									<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$task->status] ?? '' }}">
										{{ ucfirst(str_replace('_', ' ', $task->status)) }}
									</span>
									@if($task->priority)
										<span class="inline-flex items-center">
											<svg class="w-4 h-4 {{ $priorityColors[$task->priority] ?? '' }}" fill="currentColor" viewBox="0 0 20 20">
												<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
											</svg>
										</span>
									@endif
								</div>

								@if($task->description)
									<p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $task->description }}</p>
								@endif

								<div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
									<div class="flex items-center">
										<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
										</svg>
										<a href="{{ route('contracts.show', $task->contract_id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
											{{ $task->contract->contract_number }}
										</a>
									</div>

									<div class="flex items-center">
										<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
										</svg>
										{{ $task->contract->company->name }}
									</div>

									@if($task->due_date)
										<div class="flex items-center {{ $isOverdue ? 'text-red-600 font-medium' : '' }}">
											<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
											</svg>
											{{ $task->due_date->format('d.m.Y') }}
											@if($isOverdue)
												<span class="ml-1">({{ __('common.overdue') }})</span>
											@endif
										</div>
									@endif

									@if($task->assignedUser)
										<div class="flex items-center">
											<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
											</svg>
											{{ $task->assignedUser->name }}
										</div>
									@endif
								</div>
							</div>

							<div class="flex flex-col gap-2 ml-4">
								@if($task->status != 'completed')
									<form method="POST" action="{{ route('contracts.tasks.complete', [$task->contract_id, $task->id]) }}">
										@csrf
										<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
											<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
											</svg>
											{{ __('common.complete') }}
										</button>
									</form>
								@else
									<form method="POST" action="{{ route('contracts.tasks.reopen', [$task->contract_id, $task->id]) }}">
										@csrf
										<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
											{{ __('common.reopen') }}
										</button>
									</form>
								@endif

								<a href="{{ route('contracts.show', $task->contract_id) }}#tasks" class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
									{{ __('contracts.view_contract') }}
								</a>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		@else
			{{-- Empty State --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-12 text-center">
				<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
				</svg>
				<h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('contracts.messages.no_tasks') }}</h3>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					@if(request()->hasAny(['status', 'overdue']))
						{{ __('contracts.no_matching_filters') }}
					@else
						{{ __('common.no_tasks_assigned') }}
					@endif
				</p>
				@if(request()->hasAny(['status', 'overdue']))
					<div class="mt-6">
						<a href="{{ route('tasks.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
							{{ __('contracts.reset_filters') }}
						</a>
					</div>
				@endif
			</div>
		@endif

	</x-app.container>
</x-layouts.app>
