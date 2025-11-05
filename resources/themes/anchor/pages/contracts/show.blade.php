<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Contract;

	middleware(['auth', 'verified']);
	name('contracts.show');

	$contractId = request()->route('id');
	$contract = Contract::with([
		'company',
		'contractType',
		'parties.signatures',
		'attachments',
		'amendments',
		'tasks.assignedUser'
	])
	->where('id', $contractId)
	->whereHas('company', function($q) {
		$q->where('user_id', auth()->id());
	})
	->firstOrFail();

	// Status labels and colors
	$statusLabels = [
		'draft' => 'Ciornă',
		'pending' => 'În așteptare',
		'signed' => 'Semnat',
		'active' => 'Activ',
		'expired' => 'Expirat',
		'terminated' => 'Reziliat',
	];

	$statusColors = [
		'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
		'signed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
		'expired' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
		'terminated' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
	];

	// Check if all parties have signed
	$allPartiesSigned = $contract->parties->count() > 0 && $contract->parties->every(fn($party) => $party->hasSigned());

	// Task status colors
	$taskStatusColors = [
		'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
	];
?>

<x-layouts.app>
	<x-app.container class="max-w-6xl space-y-6">

		{{-- Header --}}
		<div class="flex items-start justify-between">
			<div>
				<a href="{{ route('contracts.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
					</svg>
					Înapoi la Contracte
				</a>
				<div class="flex items-center gap-3">
					<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
						{{ $contract->title }}
					</h1>
					<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$contract->status] ?? '' }}">
						{{ $statusLabels[$contract->status] ?? $contract->status }}
					</span>
				</div>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					{{ $contract->contract_number }} • {{ $contract->company->name }}
				</p>
			</div>
			<div class="flex gap-2">
				@if($contract->status == 'draft')
					<form method="POST" action="{{ route('contracts.send-for-signing', $contract->id) }}">
						@csrf
						<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
							</svg>
							Trimite spre semnare
						</button>
					</form>
				@endif
				<a href="{{ route('contracts.edit', $contract->id) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
					</svg>
					Editează
				</a>
				<form method="POST" action="{{ route('contracts.destroy', $contract->id) }}" onsubmit="return confirm('Sigur dorești să ștergi acest contract?');">
					@csrf
					@method('DELETE')
					<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
						</svg>
						Șterge
					</button>
				</form>
			</div>
		</div>

		{{-- Signing Status Alert --}}
		@if($contract->status == 'pending')
			<div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
							Contract în așteptare semnare
						</h3>
						<div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
							<p>Acest contract așteaptă să fie semnat de {{ $contract->parties->count() }} {{ $contract->parties->count() == 1 ? 'parte' : 'părți' }}.
							{{ $contract->parties->filter(fn($p) => $p->hasSigned())->count() }} din {{ $contract->parties->count() }} au semnat.</p>
						</div>
					</div>
				</div>
			</div>
		@endif

		@if($allPartiesSigned && $contract->status == 'signed')
			<div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-800 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-green-800 dark:text-green-200">
							Contract semnat complet
						</h3>
						<div class="mt-2 text-sm text-green-700 dark:text-green-300">
							<p>Toate părțile au semnat acest contract cu succes.</p>
						</div>
					</div>
				</div>
			</div>
		@endif

		{{-- Contract Information --}}
		<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
			{{-- Main Details --}}
			<div class="lg:col-span-2 space-y-6">
				{{-- Basic Information --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Informații Contract
					</h2>
					<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nr. Contract</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->contract_number }}</dd>
						</div>
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tip Contract</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->contractType->name ?? '-' }}</dd>
						</div>
						@if($contract->client_name)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Client</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->client_name }}</dd>
							</div>
						@endif
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Început</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->start_date ? $contract->start_date->format('d.m.Y') : '-' }}</dd>
						</div>
						@if($contract->end_date)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Sfârșit</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->end_date->format('d.m.Y') }}</dd>
							</div>
						@endif
						@if($contract->value)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valoare</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ number_format($contract->value, 2, ',', '.') }} RON</dd>
							</div>
						@endif
						@if($contract->billing_cycle)
							<div>
								<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ciclu Facturare</dt>
								<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1 capitalize">{{ $contract->billing_cycle }}</dd>
							</div>
						@endif
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reînnoire Automată</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->auto_renewal ? 'Da' : 'Nu' }}</dd>
						</div>
					</dl>

					@if($contract->description)
						<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Descriere</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100">{{ $contract->description }}</dd>
						</div>
					@endif
				</div>

				{{-- Contract Content --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Conținut Contract
					</h2>
					<div class="prose dark:prose-invert max-w-none">
						<div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $contract->content }}</div>
					</div>
				</div>
			</div>

			{{-- Sidebar --}}
			<div class="space-y-6">
				{{-- Quick Actions --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Acțiuni Rapide
					</h2>
					<div class="space-y-2">
						<form method="POST" action="{{ route('contracts.duplicate', $contract->id) }}">
							@csrf
							<button type="submit" class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 text-left flex items-center">
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
								</svg>
								Duplică Contract
							</button>
						</form>
						<a href="{{ route('invoices.create', ['contract' => $contract->id]) }}" class="block w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 text-center">
							Creează Factură
						</a>
						<a href="#" class="block w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 text-center">
							Descarcă PDF
						</a>
					</div>
				</div>

				{{-- Metadata --}}
				<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
						Detalii
					</h2>
					<dl class="space-y-3">
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creat la</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->created_at->format('d.m.Y H:i') }}</dd>
						</div>
						<div>
							<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ultima modificare</dt>
							<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $contract->updated_at->format('d.m.Y H:i') }}</dd>
						</div>
					</dl>
				</div>
			</div>
		</div>

		{{-- Parties --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
					Părți ({{ $contract->parties->count() }})
				</h2>
				<a href="{{ route('contracts.parties.create', $contract->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Adaugă Parte
				</a>
			</div>

			@if($contract->parties->count() > 0)
				<div class="space-y-3">
					@foreach($contract->parties as $party)
						<div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
							<div class="flex-1">
								<div class="flex items-center gap-2">
									<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $party->name }}</h3>
									@if($party->hasSigned())
										<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
											<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
												<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
											</svg>
											Semnat
										</span>
									@else
										<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
											În așteptare
										</span>
									@endif
								</div>
								<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $party->email }} • {{ $party->phone }}</p>
								@if($party->hasSigned())
									<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
										Semnat la: {{ $party->signatures()->where('code_verified', true)->first()->signed_at->format('d.m.Y H:i') }}
									</p>
								@endif
							</div>
							<div class="flex items-center gap-2">
								@if(!$party->hasSigned() && $party->signing_token)
									<a href="{{ route('signing.show', ['partyId' => $party->id, 'token' => $party->signing_token]) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
										Link semnare
									</a>
								@endif
								<form method="POST" action="{{ route('contracts.parties.destroy', [$contract->id, $party->id]) }}" onsubmit="return confirm('Sigur dorești să ștergi această parte?');">
									@csrf
									@method('DELETE')
									<button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400">
										<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
										</svg>
									</button>
								</form>
							</div>
						</div>
					@endforeach
				</div>
			@else
				<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
					Nu există părți adăugate. Adaugă părțile care trebuie să semneze contractul.
				</p>
			@endif
		</div>

		{{-- Attachments --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
					Atașamente ({{ $contract->attachments->count() }})
				</h2>
				<a href="{{ route('contracts.attachments.create', $contract->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Adaugă Atașament
				</a>
			</div>

			@if($contract->attachments->count() > 0)
				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
					@foreach($contract->attachments as $attachment)
						<div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
							<div class="flex items-center gap-3 flex-1 min-w-0">
								<svg class="w-8 h-8 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
								</svg>
								<div class="min-w-0 flex-1">
									<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $attachment->file_name }}</h3>
									<p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($attachment->file_size / 1024, 0) }} KB</p>
								</div>
							</div>
							<div class="flex items-center gap-2 flex-shrink-0">
								<a href="{{ route('contracts.attachments.download', [$contract->id, $attachment->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
									</svg>
								</a>
								<form method="POST" action="{{ route('contracts.attachments.destroy', [$contract->id, $attachment->id]) }}" onsubmit="return confirm('Sigur dorești să ștergi acest atașament?');">
									@csrf
									@method('DELETE')
									<button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400">
										<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
										</svg>
									</button>
								</form>
							</div>
						</div>
					@endforeach
				</div>
			@else
				<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
					Nu există atașamente.
				</p>
			@endif
		</div>

		{{-- Amendments --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
					Acte Adiționale ({{ $contract->amendments->count() }})
				</h2>
				<a href="{{ route('contracts.amendments.create', $contract->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Adaugă Act Adițional
				</a>
			</div>

			@if($contract->amendments->count() > 0)
				<div class="space-y-3">
					@foreach($contract->amendments as $amendment)
						<div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
							<div class="flex items-start justify-between">
								<div class="flex-1">
									<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $amendment->title }}</h3>
									<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $amendment->description }}</p>
									<p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
										Data: {{ $amendment->effective_date->format('d.m.Y') }}
									</p>
								</div>
								@if($amendment->file_path)
									<a href="{{ route('contracts.amendments.download', [$contract->id, $amendment->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
										<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
										</svg>
									</a>
								@endif
							</div>
						</div>
					@endforeach
				</div>
			@else
				<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
					Nu există acte adiționale.
				</p>
			@endif
		</div>

		{{-- Tasks --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
					Taskuri ({{ $contract->tasks->count() }})
				</h2>
				<a href="{{ route('contracts.tasks.create', $contract->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					Adaugă Task
				</a>
			</div>

			@if($contract->tasks->count() > 0)
				<div class="space-y-2">
					@foreach($contract->tasks as $task)
						<div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
							<div class="flex items-center gap-3 flex-1">
								<div>
									<div class="flex items-center gap-2">
										<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $task->title }}</h3>
										<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $taskStatusColors[$task->status] ?? '' }}">
											{{ ucfirst($task->status) }}
										</span>
									</div>
									@if($task->due_date)
										<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
											Scadență: {{ $task->due_date->format('d.m.Y') }}
											@if($task->due_date->isPast() && $task->status != 'completed')
												<span class="text-red-600">• Întârziat</span>
											@endif
										</p>
									@endif
								</div>
							</div>
							<a href="{{ route('contracts.tasks.show', [$contract->id, $task->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
								</svg>
							</a>
						</div>
					@endforeach
				</div>
			@else
				<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
					Nu există taskuri asociate.
				</p>
			@endif
		</div>

	</x-app.container>
</x-layouts.app>
