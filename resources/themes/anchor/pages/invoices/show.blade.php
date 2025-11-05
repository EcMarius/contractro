<?php
	use function Laravel\Folio\{middleware, name};
	use App\Models\Invoice;

	middleware(['auth', 'verified']);
	name('invoices.show');

	$invoiceId = request()->route('id');
	$invoice = Invoice::with(['company', 'contract', 'items'])
		->where('id', $invoiceId)
		->whereHas('company', function($q) {
			$q->where('user_id', auth()->id());
		})
		->firstOrFail();

	// Status labels and colors
	$statusLabels = [
		'draft' => 'Ciornă',
		'issued' => 'Emisă',
		'paid' => 'Plătită',
		'overdue' => 'Restantă',
		'cancelled' => 'Anulată',
	];

	$statusColors = [
		'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
		'issued' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
		'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
		'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
		'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
	];

	// Check if overdue
	$isOverdue = $invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['paid', 'cancelled']);
?>

<x-layouts.app>
	<x-app.container class="max-w-5xl space-y-6">

		{{-- Header --}}
		<div class="flex items-start justify-between">
			<div>
				<a href="{{ route('invoices.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mb-4">
					<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
					</svg>
					Înapoi la Facturi
				</a>
				<div class="flex items-center gap-3">
					<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
						Factură {{ $invoice->invoice_number }}
					</h1>
					<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$invoice->status] ?? '' }}">
						{{ $statusLabels[$invoice->status] ?? $invoice->status }}
					</span>
				</div>
				<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
					{{ $invoice->company->name }} • {{ $invoice->client_name }}
				</p>
			</div>
			<div class="flex gap-2 flex-wrap justify-end">
				@if($invoice->status == 'draft')
					<form method="POST" action="{{ route('invoices.issue', $invoice->id) }}">
						@csrf
						<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
							Emite Factura
						</button>
					</form>
				@endif

				@if(in_array($invoice->status, ['issued', 'overdue']))
					<form method="POST" action="{{ route('invoices.mark-as-paid', $invoice->id) }}" x-data="{ showDate: false }">
						@csrf
						<div x-show="!showDate">
							<button type="button" @click="showDate = true" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
								</svg>
								Marchează ca Plătită
							</button>
						</div>
						<div x-show="showDate" class="flex gap-2">
							<input type="date" name="payment_date" required value="{{ date('Y-m-d') }}"
								class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
							<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
								Confirmă
							</button>
							<button type="button" @click="showDate = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
								Anulează
							</button>
						</div>
					</form>

					<form method="POST" action="{{ route('invoices.cancel', $invoice->id) }}" onsubmit="return confirm('Sigur dorești să anulezi această factură?');">
						@csrf
						<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
							</svg>
							Anulează
						</button>
					</form>
				@endif

				@if($invoice->status != 'draft')
					<a href="#" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
						</svg>
						Descarcă PDF
					</a>
				@endif

				<a href="{{ route('invoices.edit', $invoice->id) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
					<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
					</svg>
					Editează
				</a>

				<form method="POST" action="{{ route('invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Sigur dorești să ștergi această factură?');">
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

		{{-- Overdue Alert --}}
		@if($isOverdue)
			<div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-red-800 dark:text-red-200">
							Factură Restantă
						</h3>
						<div class="mt-2 text-sm text-red-700 dark:text-red-300">
							<p>Această factură a depășit data scadentă ({{ $invoice->due_date->format('d.m.Y') }}). Este restantă de {{ $invoice->due_date->diffForHumans() }}.</p>
						</div>
					</div>
				</div>
			</div>
		@endif

		{{-- Payment Confirmation --}}
		@if($invoice->status == 'paid' && $invoice->payment_date)
			<div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-800 rounded-lg p-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="ml-3">
						<h3 class="text-sm font-medium text-green-800 dark:text-green-200">
							Factură Plătită
						</h3>
						<div class="mt-2 text-sm text-green-700 dark:text-green-300">
							<p>Această factură a fost marcată ca plătită la data de {{ $invoice->payment_date->format('d.m.Y') }}.</p>
						</div>
					</div>
				</div>
			</div>
		@endif

		{{-- Invoice Content --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
			<div class="p-8">
				{{-- Invoice Header --}}
				<div class="flex justify-between items-start mb-8">
					{{-- Provider --}}
					<div>
						<h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Furnizor</h2>
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->company->name }}</p>
						@if($invoice->company->cui)
							<p class="text-sm text-gray-600 dark:text-gray-400">CUI: {{ $invoice->company->cui }}</p>
						@endif
						@if($invoice->company->reg_com)
							<p class="text-sm text-gray-600 dark:text-gray-400">Reg. Com.: {{ $invoice->company->reg_com }}</p>
						@endif
						@if($invoice->company->address)
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $invoice->company->address }}</p>
						@endif
						@if($invoice->company->city || $invoice->company->county)
							<p class="text-sm text-gray-600 dark:text-gray-400">
								{{ $invoice->company->city }}@if($invoice->company->city && $invoice->company->county), @endif{{ $invoice->company->county }}
							</p>
						@endif
						@if($invoice->company->bank_name || $invoice->company->iban)
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
								@if($invoice->company->bank_name){{ $invoice->company->bank_name }}<br>@endif
								@if($invoice->company->iban)IBAN: {{ $invoice->company->iban }}@endif
							</p>
						@endif
					</div>

					{{-- Client --}}
					<div class="text-right">
						<h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Client</h2>
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->client_name }}</p>
						@if($invoice->client_cui)
							<p class="text-sm text-gray-600 dark:text-gray-400">CUI: {{ $invoice->client_cui }}</p>
						@endif
						@if($invoice->client_reg_com)
							<p class="text-sm text-gray-600 dark:text-gray-400">Reg. Com.: {{ $invoice->client_reg_com }}</p>
						@endif
						@if($invoice->client_address)
							<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $invoice->client_address }}</p>
						@endif
					</div>
				</div>

				{{-- Invoice Details --}}
				<div class="grid grid-cols-2 gap-4 mb-8 py-4 border-y border-gray-200 dark:border-gray-700">
					<div>
						<p class="text-sm text-gray-600 dark:text-gray-400">Nr. Factură</p>
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</p>
					</div>
					<div>
						<p class="text-sm text-gray-600 dark:text-gray-400">Data Emitere</p>
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-' }}</p>
					</div>
					<div>
						<p class="text-sm text-gray-600 dark:text-gray-400">Data Scadență</p>
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '-' }}</p>
					</div>
					@if($invoice->contract)
						<div>
							<p class="text-sm text-gray-600 dark:text-gray-400">Contract</p>
							<p class="text-sm font-medium text-gray-900 dark:text-gray-100">
								<a href="{{ route('contracts.show', $invoice->contract->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
									{{ $invoice->contract->contract_number }}
								</a>
							</p>
						</div>
					@endif
				</div>

				{{-- Line Items --}}
				<div class="mb-8">
					<table class="min-w-full">
						<thead>
							<tr class="border-b border-gray-200 dark:border-gray-700">
								<th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descriere</th>
								<th class="px-2 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cantitate</th>
								<th class="px-2 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Preț Unitar</th>
								<th class="px-2 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 dark:divide-gray-700">
							@foreach($invoice->items as $item)
								<tr>
									<td class="px-2 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $item->description }}</td>
									<td class="px-2 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($item->quantity, 2, ',', '.') }}</td>
									<td class="px-2 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($item->unit_price, 2, ',', '.') }} RON</td>
									<td class="px-2 py-3 text-sm text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($item->total_price, 2, ',', '.') }} RON</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>

				{{-- Totals --}}
				<div class="flex justify-end mb-8">
					<div class="w-80 space-y-2">
						<div class="flex justify-between text-sm">
							<span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
							<span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($invoice->subtotal_amount, 2, ',', '.') }} RON</span>
						</div>
						<div class="flex justify-between text-sm">
							<span class="text-gray-600 dark:text-gray-400">TVA (19%):</span>
							<span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($invoice->vat_amount, 2, ',', '.') }} RON</span>
						</div>
						<div class="flex justify-between text-lg font-bold pt-2 border-t-2 border-gray-300 dark:border-gray-600">
							<span class="text-gray-900 dark:text-gray-100">Total de Plată:</span>
							<span class="text-blue-600 dark:text-blue-400">{{ number_format($invoice->total_amount, 2, ',', '.') }} RON</span>
						</div>
					</div>
				</div>

				{{-- Notes --}}
				@if($invoice->notes)
					<div class="pt-4 border-t border-gray-200 dark:border-gray-700">
						<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Observații</h3>
						<p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $invoice->notes }}</p>
					</div>
				@endif
			</div>
		</div>

		{{-- Metadata --}}
		<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
			<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
				Detalii
			</h2>
			<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
				<div>
					<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creat la</dt>
					<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $invoice->created_at->format('d.m.Y H:i') }}</dd>
				</div>
				<div>
					<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ultima modificare</dt>
					<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $invoice->updated_at->format('d.m.Y H:i') }}</dd>
				</div>
				@if($invoice->payment_date)
					<div>
						<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Plată</dt>
						<dd class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $invoice->payment_date->format('d.m.Y') }}</dd>
					</div>
				@endif
			</dl>
		</div>

	</x-app.container>
</x-layouts.app>
