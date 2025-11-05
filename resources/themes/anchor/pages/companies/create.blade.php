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
				Înapoi la Companii
			</a>
			<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
				Adaugă Companie Nouă
			</h1>
			<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
				Completează informațiile despre compania ta
			</p>
		</div>

		{{-- Form --}}
		<form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" class="space-y-6">
			@csrf

			{{-- Company Information --}}
			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
					Informații Companie
				</h2>

				<div class="space-y-4">
					{{-- Name --}}
					<div>
						<label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Denumire <span class="text-red-500">*</span>
						</label>
						<input type="text" name="name" id="name" required
							value="{{ old('name') }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						@error('name')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- CUI --}}
					<div>
						<label for="cui" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							CUI (Cod Unic de Identificare)
						</label>
						<input type="text" name="cui" id="cui"
							value="{{ old('cui') }}"
							placeholder="RO12345678"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
							Format: RO12345678 sau doar cifrele
						</p>
						@error('cui')
							<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					{{-- Reg Com --}}
					<div>
						<label for="reg_com" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Nr. Reg. Com.
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
					Informații de Contact
				</h2>

				<div class="space-y-4">
					{{-- Address --}}
					<div>
						<label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Adresă
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
								Oraș
							</label>
							<input type="text" name="city" id="city"
								value="{{ old('city') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- County --}}
						<div>
							<label for="county" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Județ
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
								Email
							</label>
							<input type="email" name="email" id="email"
								value="{{ old('email') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>

						{{-- Phone --}}
						<div>
							<label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
								Telefon
							</label>
							<input type="tel" name="phone" id="phone"
								value="{{ old('phone') }}"
								class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
						</div>
					</div>

					{{-- Website --}}
					<div>
						<label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Website
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
					Informații Bancare
				</h2>

				<div class="space-y-4">
					{{-- Bank Name --}}
					<div>
						<label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							Nume Bancă
						</label>
						<input type="text" name="bank_name" id="bank_name"
							value="{{ old('bank_name') }}"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
					</div>

					{{-- IBAN --}}
					<div>
						<label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
							IBAN
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
					Logo Companie
				</h2>

				<div>
					<label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
						Încarcă Logo
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
						PNG, JPG, JPEG sau SVG. Maximum 2MB.
					</p>
					@error('logo')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
					@enderror
				</div>
			</div>

			{{-- Actions --}}
			<div class="flex items-center justify-end gap-3">
				<a href="{{ route('companies.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					Anulează
				</a>
				<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
					Salvează Compania
				</button>
			</div>
		</form>

	</x-app.container>
</x-layouts.app>
