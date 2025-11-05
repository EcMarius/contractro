<?php
	use function Laravel\Folio\{name};
	use App\Models\ContractParty;

	name('signing.show');

	// NO AUTHENTICATION REQUIRED - this is a public signing page
	// Party ID and token are passed in the route

	$partyId = request()->route('partyId');
	$token = request()->route('token');

	$party = ContractParty::with(['contract.contractType', 'contract.company'])->findOrFail($partyId);

	if ($party->signing_token !== $token) {
		abort(403, 'Invalid signing token');
	}

	$contract = $party->contract;

	// Check if already signed
	$alreadySigned = $party->hasSigned();
	$signature = $party->signatures()->where('code_verified', true)->first();
?>

<x-layouts.app>
	<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
		<div class="max-w-4xl mx-auto">

			@if($alreadySigned)
				{{-- Already Signed Message --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center">
					<div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900">
						<svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
						</svg>
					</div>
					<h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-gray-100">
						Contract deja semnat
					</h2>
					<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
						Ai semnat deja acest contract la data de {{ $signature->signed_at->format('d.m.Y H:i') }}.
					</p>
				</div>
			@else
				{{-- Signing Form --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
					{{-- Header --}}
					<div class="bg-blue-600 px-6 py-8 text-white">
						<h1 class="text-3xl font-bold">
							Semnare Contract
						</h1>
						<p class="mt-2 text-blue-100">
							{{ $contract->company->name }}
						</p>
					</div>

					{{-- Content --}}
					<div class="p-6 space-y-6">
						{{-- Contract Information --}}
						<div>
							<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
								Informații Contract
							</h2>
							<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Titlu Contract</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->title }}</dd>
								</div>
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nr. Contract</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->contract_number }}</dd>
								</div>
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tip Contract</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->contractType->name ?? '-' }}</dd>
								</div>
								@if($contract->value)
									<div>
										<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valoare</dt>
										<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ number_format($contract->value, 2, ',', '.') }} RON</dd>
									</div>
								@endif
							</dl>
						</div>

						{{-- Your Information --}}
						<div class="pt-4 border-t border-gray-200 dark:border-gray-700">
							<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
								Datele Tale
							</h2>
							<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nume</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->name }}</dd>
								</div>
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->email }}</dd>
								</div>
								<div>
									<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</dt>
									<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $party->phone }}</dd>
								</div>
							</dl>
						</div>

						{{-- Contract Content --}}
						<div class="pt-4 border-t border-gray-200 dark:border-gray-700">
							<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
								Conținut Contract
							</h2>
							<div class="max-h-96 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
								<div class="prose dark:prose-invert max-w-none text-sm">
									<div class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $contract->content }}</div>
								</div>
							</div>
						</div>

						{{-- Signing Process --}}
						<div class="pt-4 border-t border-gray-200 dark:border-gray-700" x-data="signingProcess()">
							<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
								Proces Semnare
							</h2>

							{{-- Step 1: Initiate SMS --}}
							<div x-show="step === 1" class="space-y-4">
								<p class="text-sm text-gray-600 dark:text-gray-400">
									Pentru a semna acest contract, vei primi un cod de verificare prin SMS la numărul de telefon <strong>{{ $party->phone }}</strong>.
								</p>
								<div x-show="error" x-text="error" class="p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm"></div>
								<button @click="initiateSms()" :disabled="loading" class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
									<span x-show="!loading">Trimite cod SMS</span>
									<span x-show="loading">Se trimite...</span>
								</button>
							</div>

							{{-- Step 2: Verify SMS Code --}}
							<div x-show="step === 2" class="space-y-4">
								<p class="text-sm text-gray-600 dark:text-gray-400">
									Un cod de verificare a fost trimis la <strong>{{ $party->phone }}</strong>. Introdu codul primit pentru a continua.
								</p>
								<div>
									<label for="sms_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
										Cod Verificare SMS
									</label>
									<input type="text" x-model="smsCode" id="sms_code" maxlength="6" placeholder="123456"
										class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
								</div>
								<div x-show="error" x-text="error" class="p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm"></div>
								<div class="flex gap-3">
									<button @click="step = 1; error = ''" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
										Înapoi
									</button>
									<button @click="verifySms()" :disabled="loading || smsCode.length !== 6" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
										<span x-show="!loading">Verifică codul</span>
										<span x-show="loading">Se verifică...</span>
									</button>
								</div>
							</div>

							{{-- Step 3: Upload Signature --}}
							<div x-show="step === 3" class="space-y-4">
								<p class="text-sm text-gray-600 dark:text-gray-400">
									Cod verificat cu succes! Acum încarcă o imagine cu semnătura ta scrisă de mână.
								</p>
								<div>
									<label for="signature_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
										Imagine Semnătură
									</label>
									<input type="file" @change="signatureFile = $event.target.files[0]" id="signature_file" accept="image/*"
										class="block w-full text-sm text-gray-500 dark:text-gray-400
											file:mr-4 file:py-2 file:px-4
											file:rounded-md file:border-0
											file:text-sm file:font-medium
											file:bg-blue-50 file:text-blue-700
											hover:file:bg-blue-100
											dark:file:bg-blue-900 dark:file:text-blue-300">
									<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
										PNG, JPG sau JPEG. Maximum 5MB. Semnează pe o hârtie albă și fotografiază.
									</p>
								</div>
								<div x-show="error" x-text="error" class="p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm"></div>
								<button @click="uploadSignature()" :disabled="loading || !signatureFile" class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
									<span x-show="!loading">Semnează contractul</span>
									<span x-show="loading">Se semnează...</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			@endif

		</div>
	</div>

	<script>
		function signingProcess() {
			return {
				step: 1,
				loading: false,
				error: '',
				smsCode: '',
				signatureFile: null,

				async initiateSms() {
					this.loading = true;
					this.error = '';

					try {
						const response = await fetch('{{ route('signing.initiate-sms', ['partyId' => $partyId, 'token' => $token]) }}', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-CSRF-TOKEN': '{{ csrf_token() }}'
							}
						});

						const data = await response.json();

						if (response.ok) {
							this.step = 2;
						} else {
							this.error = data.message || 'A apărut o eroare. Te rugăm să încerci din nou.';
						}
					} catch (e) {
						this.error = 'Eroare de rețea. Te rugăm să verifici conexiunea.';
					} finally {
						this.loading = false;
					}
				},

				async verifySms() {
					this.loading = true;
					this.error = '';

					try {
						const response = await fetch('{{ route('signing.verify-sms', ['partyId' => $partyId, 'token' => $token]) }}', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-CSRF-TOKEN': '{{ csrf_token() }}'
							},
							body: JSON.stringify({ code: this.smsCode })
						});

						const data = await response.json();

						if (response.ok) {
							this.step = 3;
						} else {
							this.error = data.message || 'Cod invalid. Te rugăm să încerci din nou.';
						}
					} catch (e) {
						this.error = 'Eroare de rețea. Te rugăm să verifici conexiunea.';
					} finally {
						this.loading = false;
					}
				},

				async uploadSignature() {
					this.loading = true;
					this.error = '';

					const formData = new FormData();
					formData.append('signature', this.signatureFile);

					try {
						const response = await fetch('{{ route('signing.upload-signature', ['partyId' => $partyId, 'token' => $token]) }}', {
							method: 'POST',
							headers: {
								'X-CSRF-TOKEN': '{{ csrf_token() }}'
							},
							body: formData
						});

						const data = await response.json();

						if (response.ok) {
							// Redirect to success page
							window.location.href = '{{ route('signing.success', ['partyId' => $partyId, 'token' => $token]) }}';
						} else {
							this.error = data.message || 'A apărut o eroare la încărcarea semnăturii.';
						}
					} catch (e) {
						this.error = 'Eroare de rețea. Te rugăm să verifici conexiunea.';
					} finally {
						this.loading = false;
					}
				}
			}
		}
	</script>
</x-layouts.app>
