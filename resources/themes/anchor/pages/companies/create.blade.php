<?php
    use function Laravel\Folio\{middleware, name};

	middleware(['auth', 'verified']);
    name('companies.create');
?>

<x-layouts.app>
	<x-app.container class="max-w-3xl space-y-6">

		{{-- Header --}}
		<div>
			<a href="{{ route('companies.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
				<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				{{ __('common.back') }} {{ __('companies.companies') }}
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				{{ __('companies.new_company') }}
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				{{ __('companies.messages.fill_company_info') }}
			</p>
		</div>

		{{-- Form --}}
		<form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" class="space-y-6"
			x-data="{
				loading: false,
				lookupResult: null,
				country: 'RO',
				async lookupCompany() {
					const cui = document.getElementById('cui').value;
					if (!cui) {
						alert('{{ __('companies.messages.enter_cui_first') }}');
						return;
					}

					this.loading = true;
					this.lookupResult = null;

					try {
						const response = await fetch('/api/company/lookup', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
							},
							body: JSON.stringify({
								registration_code: cui,
								country: this.country
							})
						});

						const result = await response.json();
						this.lookupResult = result;

						if (result.found && result.data) {
							// Auto-fill form fields
							if (result.data.name) document.getElementById('name').value = result.data.name;
							if (result.data.address) document.getElementById('address').value = result.data.address;
							if (result.data.phone) document.getElementById('phone').value = result.data.phone;
							if (result.data.registration_number) document.getElementById('reg_com').value = result.data.registration_number;
							if (result.data.vat_number) document.getElementById('cui').value = result.data.vat_number;

							alert('✅ {{ __('companies.messages.company_data_loaded') }}');
						} else {
							alert('❌ {{ __('companies.messages.company_not_found') }}');
						}
					} catch (error) {
						alert('{{ __('companies.messages.lookup_error') }}: ' + error.message);
					} finally {
						this.loading = false;
					}
				}
			}">
			@csrf

			{{-- Company Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					{{ __('companies.company_information') }}
				</h2>

				<div class="space-y-4">
					{{-- CUI / Registration Code with Auto-Fill --}}
					<div>
						<label for="cui" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
							{{ __('companies.cui') }} / Registration Code
						</label>

						<div class="flex gap-2">
							{{-- Country Selector --}}
							<select x-model="country" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" style="max-width: 100px;">
								<option value="RO">🇷🇴 RO</option>
								<option value="US">🇺🇸 US</option>
								<option value="GB">🇬🇧 GB</option>
								<option value="DE">🇩🇪 DE</option>
								<option value="FR">🇫🇷 FR</option>
								<option value="ES">🇪🇸 ES</option>
								<option value="IT">🇮🇹 IT</option>
								<option value="NL">🇳🇱 NL</option>
								<option value="BE">🇧🇪 BE</option>
								<option value="AT">🇦🇹 AT</option>
								<option value="PL">🇵🇱 PL</option>
								<option value="SE">🇸🇪 SE</option>
							</select>

							{{-- CUI Input --}}
							<input type="text" name="cui" id="cui"
								value="{{ old('cui') }}"
								placeholder="RO12345678"
								class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">

							{{-- Lookup Button --}}
							<button type="button" @click="lookupCompany()"
								:disabled="loading"
								class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-50 transition">
								<span x-show="!loading">🔍 {{ __('companies.auto_fill') }}</span>
								<span x-show="loading" x-cloak>
									<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
									</svg>
								</span>
							</button>
						</div>

						<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
							💡 {{ __('companies.helpers.auto_fill_hint') }}
						</p>
						@error('cui')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- Name --}}
					<div>
						<label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.name') }} <span class="text-red-500">*</span>
						</label>
						<input type="text" name="name" id="name" required
							value="{{ old('name') }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						@error('name')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- Reg Com --}}
					<div>
						<label for="reg_com" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.reg_com') }}
						</label>
						<input type="text" name="reg_com" id="reg_com"
							value="{{ old('reg_com') }}"
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
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('address') }}</textarea>
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
								value="{{ old('city') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- County --}}
						<div>
							<label for="county" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.county') }}
							</label>
							<input type="text" name="county" id="county"
								value="{{ old('county') }}"
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
								value="{{ old('email') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- Phone --}}
						<div>
							<label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								{{ __('companies.phone') }}
							</label>
							<input type="tel" name="phone" id="phone"
								value="{{ old('phone') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>
					</div>

					{{-- Website --}}
					<div>
						<label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.website') }}
						</label>
						<input type="url" name="website" id="website"
							value="{{ old('website') }}"
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
							value="{{ old('bank_name') }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>

					{{-- IBAN --}}
					<div>
						<label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							{{ __('companies.iban') }}
						</label>
						<input type="text" name="iban" id="iban"
							value="{{ old('iban') }}"
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

				<div>
					<label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
						{{ __('companies.upload_logo') }}
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
				<a href="{{ route('companies.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					{{ __('common.cancel') }}
				</a>
				<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
					{{ __('companies.messages.save_company') }}
				</button>
			</div>
		</form>

	</x-app.container>
</x-layouts.app>
