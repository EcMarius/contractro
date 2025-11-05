<?php
    use function Laravel\Folio\{middleware, name};
	use App\Models\Company;

	middleware(['auth', 'verified']);
    name('companies.index');

	$user = auth()->user();
	$companies = Company::where('user_id', $user->id)
		->withCount(['contracts', 'invoices'])
		->latest()
		->get();
?>

<x-layouts.app>
	<x-app.container class="space-y-6">

		{{-- Header --}}
		<div class="flex flex-col md:flex-row md:items-center md:justify-between">
			<div>
				<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
					{{ __('companies.my_companies') }}
				</h1>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					{{ __('companies.messages.manage_companies') }}
				</p>
			</div>

			<div class="mt-4 md:mt-0">
				<a href="{{ route('companies.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					{{ __('companies.create_company') }}
				</a>
			</div>
		</div>

		{{-- Companies List --}}
		@if($companies->count() > 0)
			<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
				@foreach($companies as $company)
					<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden hover:shadow-md transition-shadow">
						<div class="p-6">
							{{-- Logo or Icon --}}
							<div class="flex items-start justify-between mb-4">
								<div class="flex items-center">
									@if($company->logo_path)
										<img src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }}" class="w-12 h-12 rounded-full object-cover">
									@else
										<div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
											<svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
											</svg>
										</div>
									@endif
									<div class="ml-3">
										<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
											{{ $company->name }}
										</h3>
										@if($company->cui)
											<p class="text-sm text-gray-500 dark:text-gray-400">
												{{ __('companies.cui') }}: {{ $company->cui }}
											</p>
										@endif
									</div>
								</div>
							</div>

							{{-- Company Info --}}
							<div class="space-y-2 mb-4">
								@if($company->email)
									<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
										<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
										</svg>
										{{ $company->email }}
									</div>
								@endif
								@if($company->phone)
									<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
										<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
										</svg>
										{{ $company->phone }}
									</div>
								@endif
								@if($company->city)
									<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
										<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
										</svg>
										{{ $company->city }}{{ $company->county ? ', ' . $company->county : '' }}
									</div>
								@endif
							</div>

							{{-- Statistics --}}
							<div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
								<div class="text-center">
									<div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
										{{ $company->contracts_count }}
									</div>
									<div class="text-xs text-gray-500 dark:text-gray-400">
										{{ __('contracts.contracts') }}
									</div>
								</div>
								<div class="text-center">
									<div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
										{{ $company->invoices_count }}
									</div>
									<div class="text-xs text-gray-500 dark:text-gray-400">
										{{ __('invoices.invoices') }}
									</div>
								</div>
							</div>

							{{-- Actions --}}
							<div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
								<a href="{{ route('companies.show', $company) }}" class="flex-1 text-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-md transition-colors">
									{{ __('companies.view_company') }}
								</a>
								<a href="{{ route('companies.edit', $company) }}" class="flex-1 text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
									{{ __('common.edit') }}
								</a>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		@else
			{{-- Empty State --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
				<div class="px-6 py-12 text-center">
					<svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
					</svg>
					<h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
						{{ __('companies.messages.no_companies') }}
					</h3>
					<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
						{{ __('companies.messages.first_company_prompt') }}
					</p>
					<div class="mt-6">
						<a href="{{ route('companies.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
							<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
							</svg>
							{{ __('companies.create_company') }}
						</a>
					</div>
				</div>
			</div>
		@endif

	</x-app.container>
</x-layouts.app>
