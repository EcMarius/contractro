<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Company;

	middleware(['auth', 'verified']);
	name('companies.edit');

	$companyId = request()->route('id');
	$company = Company::where('id', $companyId)
		->where('user_id', auth()->id())
		->firstOrFail();
?>

<x-layouts.app>
	<x-app.container class="max-w-3xl space-y-6">

		{{-- Header --}}
		<div>
			<a href="{{ route('companies.show', $company->id) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
				<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				{{ __('common.back') }} {{ $company->name }}
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				{{ __('companies.edit_company') }}
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				{{ __('companies.messages.update_company_info') }}
			</p>
		</div>

		{{-- Form --}}
		<form method="POST" action="{{ route('companies.update', $company->id) }}" enctype="multipart/form-data" class="space-y-6">
			@csrf
			@method('PUT')

			{{-- Company Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					{{ __('companies.company_information') }}
				</h2>

				<div class="space-y-4">
					{{-- Name --}}
					<div>
						<label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.name') }} <span class="text-red-500">*</span>
						</label>
						<input type="text" name="name" id="name" required
							value="{{ old('name', $company->name) }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						@error('name')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- CUI --}}
					<div>
						<label for="cui" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.cui') }}
						</label>
						<input type="text" name="cui" id="cui"
							value="{{ old('cui', $company->cui) }}"
							placeholder="RO12345678"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
							{{ __('companies.helpers.cui_format') }}
						</p>
						@error('cui')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- Reg Com --}}
					<div>
						<label for="reg_com" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.reg_com') }}
						</label>
						<input type="text" name="reg_com" id="reg_com"
							value="{{ old('reg_com', $company->reg_com) }}"
							placeholder="J40/1234/2023"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						@error('reg_com')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>
			</div>

			{{-- Contact Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					{{ __('companies.contact_information') }}
				</h2>

				<div class="space-y-4">
					{{-- Address --}}
					<div>
						<label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.address') }}
						</label>
						<textarea name="address" id="address" rows="2"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('address', $company->address) }}</textarea>
						@error('address')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						{{-- City --}}
						<div>
							<label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.city') }}
							</label>
							<input type="text" name="city" id="city"
								value="{{ old('city', $company->city) }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- County --}}
						<div>
							<label for="county" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.county') }}
							</label>
							<input type="text" name="county" id="county"
								value="{{ old('county', $company->county) }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>
					</div>

					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						{{-- Email --}}
						<div>
							<label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.email') }}
							</label>
							<input type="email" name="email" id="email"
								value="{{ old('email', $company->email) }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- Phone --}}
						<div>
							<label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.phone') }}
							</label>
							<input type="tel" name="phone" id="phone"
								value="{{ old('phone', $company->phone) }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>
					</div>

					{{-- Website --}}
					<div>
						<label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.website') }}
						</label>
						<input type="url" name="website" id="website"
							value="{{ old('website', $company->website) }}"
							placeholder="https://exemplu.ro"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>
				</div>
			</div>

			{{-- Banking Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					{{ __('companies.banking_information') }}
				</h2>

				<div class="space-y-4">
					{{-- Bank Name --}}
					<div>
						<label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.bank_name') }}
						</label>
						<input type="text" name="bank_name" id="bank_name"
							value="{{ old('bank_name', $company->bank_name) }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>

					{{-- IBAN --}}
					<div>
						<label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.iban') }}
						</label>
						<input type="text" name="iban" id="iban"
							value="{{ old('iban', $company->iban) }}"
							placeholder="RO49AAAA1B31007593840000"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>
				</div>
			</div>

			{{-- Logo Upload --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					{{ __('companies.logo') }}
				</h2>

				{{-- Current Logo --}}
				@if($company->logo_path)
					<div class="mb-4">
						<p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('companies.messages.current_logo') }}:</p>
						<div class="flex items-center gap-4">
							<img src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }}" class="w-24 h-24 rounded-lg object-contain bg-white dark:bg-gray-800 p-2 border border-gray-200 dark:border-gray-700">
							<div>
								<label for="remove_logo" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
									<input type="checkbox" name="remove_logo" id="remove_logo" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 mr-2">
									{{ __('companies.messages.remove_current_logo') }}
								</label>
							</div>
						</div>
					</div>
				@endif

				<div>
					<label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
						{{ $company->logo_path ? __('companies.messages.replace_logo') : __('companies.upload_logo') }}
					</label>
					<input type="file" name="logo" id="logo" accept="image/*"
						class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
							file:mr-4 file:py-2 file:px-4
							file:rounded-md file:border-0
							file:text-sm file:font-medium
							file:bg-blue-50 file:text-blue-700
							hover:file:bg-blue-100
							dark:file:bg-blue-900 dark:file:text-blue-300">
					<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
						{{ __('companies.helpers.logo_requirements', ['max' => 2]) }}
					</p>
					@error('logo')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
					@enderror
				</div>
			</div>

			{{-- Actions --}}
			<div class="flex items-center justify-end gap-3">
				<a href="{{ route('companies.show', $company->id) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					{{ __('common.cancel') }}
				</a>
				<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
					{{ __('companies.messages.save_changes') }}
				</button>
			</div>
		</form>

	</x-app.container>
</x-layouts.app>
