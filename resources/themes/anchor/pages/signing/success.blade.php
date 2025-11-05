<?php
	use function Laravel\Folio\{name};
	use App\Models\ContractParty;

	name('signing.success');

	// NO AUTHENTICATION REQUIRED - this is a public page

	$partyId = request()->route('partyId');
	$token = request()->route('token');

	$party = ContractParty::with(['contract.company'])->findOrFail($partyId);

	if ($party->signing_token !== $token) {
		abort(403, 'Invalid signing token');
	}

	$contract = $party->contract;
	$signature = $party->signatures()->where('code_verified', true)->first();

	if (!$signature) {
		abort(404, 'Signature not found');
	}
?>

<x-layouts.app>
	<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
		<div class="max-w-2xl mx-auto">

			<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
				{{-- Success Icon --}}
				<div class="bg-green-600 px-6 py-12 text-center">
					<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
						<svg class="h-10 w-10 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
						</svg>
					</div>
					<h1 class="text-3xl font-bold text-white">
						Contract Semnat cu Succes!
					</h1>
					<p class="mt-2 text-green-100">
						Mulțumim pentru semnarea contractului
					</p>
				</div>

				{{-- Details --}}
				<div class="p-6 space-y-6">
					<div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
						<div class="flex">
							<div class="flex-shrink-0">
								<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
								</svg>
							</div>
							<div class="ml-3">
								<h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
									Informații Semnare
								</h3>
								<div class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
									<p><strong>Contract:</strong> {{ $contract->contract_number }}</p>
									<p><strong>Titlu:</strong> {{ $contract->title }}</p>
									<p><strong>Companie:</strong> {{ $contract->company->name }}</p>
									<p><strong>Semnat la:</strong> {{ $signature->signed_at->format('d.m.Y H:i') }}</p>
									<p><strong>Adresa IP:</strong> {{ $signature->ip_address }}</p>
								</div>
							</div>
						</div>
					</div>

					{{-- Signature Details --}}
					<div>
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
							Detalii Semnătură
						</h2>
						<dl class="grid grid-cols-1 gap-4">
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nume</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->name }}</dd>
							</div>
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->email }}</dd>
							</div>
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon Verificat</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->phone }}</dd>
							</div>
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Metoda Verificare</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">SMS + Semnătură Electronică</dd>
							</div>
						</dl>
					</div>

					{{-- Signature Image --}}
					@if($signature->signature_image_path)
						<div>
							<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
								Semnătura Ta
							</h2>
							<div class="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
								<img src="{{ Storage::url($signature->signature_image_path) }}" alt="Semnătură" class="max-w-xs mx-auto">
							</div>
						</div>
					@endif

					{{-- eIDAS Compliance Notice --}}
					<div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
						<div class="flex">
							<div class="flex-shrink-0">
								<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
							</div>
							<div class="ml-3">
								<h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">
									Conformitate eIDAS (EU 910/2014)
								</h3>
								<div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
									<p>Această semnătură electronică respectă standardele UE pentru semnături electronice avansate. Următoarele date au fost înregistrate:</p>
									<ul class="mt-2 space-y-1 list-disc list-inside text-xs">
										<li>Data și ora exactă a semnării</li>
										<li>Verificare identitate prin SMS (2FA)</li>
										<li>Adresa IP și user agent</li>
										<li>Hash criptografic al documentului</li>
										<li>Imagine semnătură scrisă de mână</li>
									</ul>
								</div>
							</div>
						</div>
					</div>

					{{-- Next Steps --}}
					<div>
						<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
							Ce urmează?
						</h2>
						<ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
							<li class="flex items-start">
								<svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span>Vei primi o copie a contractului semnat pe email în maximum 24 de ore</span>
							</li>
							<li class="flex items-start">
								<svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span>Compania {{ $contract->company->name }} a fost notificată despre semnarea ta</span>
							</li>
							<li class="flex items-start">
								<svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
								</svg>
								<span>Contractul devine activ când toate părțile au semnat</span>
							</li>
						</ul>
					</div>

					{{-- Contact Information --}}
					@if($contract->company->email || $contract->company->phone)
						<div class="pt-4 border-t border-gray-200 dark:border-gray-700">
							<h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
								Ai întrebări?
							</h2>
							<p class="text-sm text-gray-600 dark:text-gray-400">
								Contactează compania {{ $contract->company->name }}:
							</p>
							<div class="mt-2 space-y-1 text-sm">
								@if($contract->company->email)
									<p class="text-gray-900 dark:text-gray-100">
										<strong>Email:</strong> <a href="mailto:{{ $contract->company->email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $contract->company->email }}</a>
									</p>
								@endif
								@if($contract->company->phone)
									<p class="text-gray-900 dark:text-gray-100">
										<strong>Telefon:</strong> <a href="tel:{{ $contract->company->phone }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $contract->company->phone }}</a>
									</p>
								@endif
							</div>
						</div>
					@endif

					{{-- Action Button --}}
					<div class="pt-4">
						<p class="text-xs text-center text-gray-500 dark:text-gray-400">
							Poți închide această pagină în siguranță.
						</p>
					</div>
				</div>
			</div>

		</div>
	</div>
</x-layouts.app>
