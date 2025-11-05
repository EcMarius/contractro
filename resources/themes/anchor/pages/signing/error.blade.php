<?php
	use function Laravel\Folio\{name};

	name('signing.error');

	// NO AUTHENTICATION REQUIRED - this is a public error page

	$error = request('error', 'Link invalid sau expirat');
?>

<x-layouts.app>
	<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
		<div class="max-w-2xl mx-auto">

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
				{{-- Error Icon --}}
				<div class="bg-red-600 px-6 py-12 text-center">
					<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
						<svg class="h-10 w-10 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
						</svg>
					</div>
					<h1 class="text-3xl font-bold text-white">
						Eroare Semnare Contract
					</h1>
					<p class="mt-2 text-red-100">
						Nu am putut procesa cererea ta de semnare
					</p>
				</div>

				{{-- Error Details --}}
				<div class="p-6 space-y-6">
					<div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg p-4">
						<div class="flex">
							<div class="flex-shrink-0">
								<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
								</svg>
							</div>
							<div class="ml-3">
								<h3 class="text-sm font-medium text-red-800 dark:text-red-200">
									{{ $error }}
								</h3>
							</div>
						</div>
					</div>

					{{-- Possible Reasons --}}
					<div>
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
							Motive posibile:
						</h2>
						<ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
							<li class="flex items-start">
								<svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span><strong>Link-ul a expirat</strong> - Link-urile de semnare pot expira după o anumită perioadă</span>
							</li>
							<li class="flex items-start">
								<svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span><strong>Link-ul este invalid</strong> - Asigură-te că ai copiat link-ul complet din email</span>
							</li>
							<li class="flex items-start">
								<svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span><strong>Ai semnat deja</strong> - Dacă ai semnat deja contractul, nu mai poți accesa link-ul</span>
							</li>
							<li class="flex items-start">
								<svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span><strong>Contractul a fost anulat</strong> - Compania ar putea să fi anulat sau modificat contractul</span>
							</li>
						</ul>
					</div>

					{{-- What to Do --}}
					<div class="pt-4 border-t border-gray-200 dark:border-gray-700">
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
							Ce poți face?
						</h2>
						<div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
							<div class="flex items-start">
								<div class="flex-shrink-0">
									<div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 font-semibold text-xs">
										1
									</div>
								</div>
								<div class="ml-3">
									<h3 class="font-medium text-gray-900 dark:text-gray-100">Verifică email-ul</h3>
									<p class="mt-1">Caută în inbox sau spam email-ul cu link-ul de semnare și asigură-te că folosești cel mai recent link.</p>
								</div>
							</div>

							<div class="flex items-start">
								<div class="flex-shrink-0">
									<div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 font-semibold text-xs">
										2
									</div>
								</div>
								<div class="ml-3">
									<h3 class="font-medium text-gray-900 dark:text-gray-100">Contactează compania</h3>
									<p class="mt-1">Dacă problema persistă, contactează compania care ți-a trimis contractul pentru un nou link de semnare.</p>
								</div>
							</div>

							<div class="flex items-start">
								<div class="flex-shrink-0">
									<div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 font-semibold text-xs">
										3
									</div>
								</div>
								<div class="ml-3">
									<h3 class="font-medium text-gray-900 dark:text-gray-100">Verifică statusul</h3>
									<p class="mt-1">Poate ai semnat deja contractul. Verifică emailul pentru confirmarea de semnare.</p>
								</div>
							</div>
						</div>
					</div>

					{{-- Help Section --}}
					<div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
						<div class="flex">
							<div class="flex-shrink-0">
								<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
								</svg>
							</div>
							<div class="ml-3">
								<h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">
									Ai nevoie de ajutor?
								</h3>
								<div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
									<p>Pentru asistență tehnică sau întrebări legate de procesul de semnare, te rugăm să contactezi direct compania care ți-a trimis contractul.</p>
								</div>
							</div>
						</div>
					</div>

					{{-- Security Notice --}}
					<div class="pt-4">
						<p class="text-xs text-center text-gray-500 dark:text-gray-400">
							<svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
							</svg>
							Link-urile de semnare sunt unice și securizate. Nu împărtăși link-ul cu alte persoane.
						</p>
					</div>
				</div>
			</div>

		</div>
	</div>
</x-layouts.app>
