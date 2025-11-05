<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create invoice
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber(
                $data['company_id'],
                $data['series'] ?? 'FACT'
            );

            // Create invoice
            $invoice = Invoice::create(array_merge($data, [
                'invoice_number' => $invoiceNumber,
                'status' => $data['status'] ?? 'draft',
                'items' => $data['items'] ?? [],
            ]));

            // Calculate totals
            $invoice->calculateTotals();
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * Create invoice from contract
     */
    public function createFromContract(Contract $contract, ?array $additionalData = []): Invoice
    {
        $company = $contract->company;

        // Get client info from first party
        $client = $contract->contractParties()->where('party_type', 'client')->first();

        $invoiceData = [
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'series' => $additionalData['series'] ?? $company->getInvoiceSettings()['series'],
            'client_name' => $client?->display_name ?? 'Client',
            'client_cui' => $client?->company_cui,
            'client_address' => $client?->address,
            'currency' => $contract->currency,
            'issue_date' => now(),
            'due_date' => now()->addDays($company->getInvoiceSettings()['default_due_days'] ?? 30),
            'vat_rate' => $company->getInvoiceSettings()['vat_rate'] ?? 19,
            'items' => $additionalData['items'] ?? [],
        ];

        // If contract has a value, add it as an item
        if ($contract->value > 0) {
            $invoiceData['items'][] = [
                'description' => $contract->title,
                'quantity' => 1,
                'unit' => 'buc',
                'unit_price' => $contract->value,
                'total' => $contract->value,
            ];
        }

        return $this->create(array_merge($invoiceData, $additionalData));
    }

    /**
     * Update invoice
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        // Recalculate if items changed
        if (isset($data['items'])) {
            $invoice->calculateTotals();
            $invoice->save();
        }

        return $invoice->fresh();
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, ?string $paymentDate = null): bool
    {
        return $invoice->markAsPaid($paymentDate);
    }

    /**
     * Mark invoice as issued
     */
    public function issue(Invoice $invoice): bool
    {
        if ($invoice->status !== 'draft') {
            throw new \Exception('Doar facturile în ciornă pot fi emise');
        }

        // Validate Romanian format
        $errors = $invoice->validateRomanianFormat();
        if (!empty($errors)) {
            throw new \Exception('Factură invalidă: ' . implode(', ', $errors));
        }

        return $invoice->update(['status' => 'issued']);
    }

    /**
     * Cancel invoice
     */
    public function cancel(Invoice $invoice, ?string $reason = null): bool
    {
        if ($invoice->status === 'paid') {
            throw new \Exception('Nu se poate anula o factură plătită');
        }

        return $invoice->update([
            'status' => 'cancelled',
            'notes' => ($invoice->notes ? $invoice->notes . "\n" : '') . "Anulată: " . ($reason ?? 'Fără motiv specificat'),
        ]);
    }

    /**
     * Add item to invoice
     */
    public function addItem(Invoice $invoice, array $itemData): Invoice
    {
        $invoice->addItem(
            $itemData['description'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['unit'] ?? 'buc'
        );

        return $invoice->fresh();
    }

    /**
     * Get overdue invoices for a company
     */
    public function getOverdueInvoices(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::where('company_id', $companyId)
            ->whereIn('status', ['issued'])
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Mark overdue invoices
     */
    public function markOverdueInvoices(): int
    {
        $overdue = Invoice::where('status', 'issued')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdue as $invoice) {
            $invoice->update(['status' => 'overdue']);
        }

        return $overdue->count();
    }

    /**
     * Get invoice statistics for a company
     */
    public function getStatistics(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        return [
            'total_invoices' => $company->invoices()->count(),
            'draft_invoices' => $company->invoices()->where('status', 'draft')->count(),
            'issued_invoices' => $company->invoices()->where('status', 'issued')->count(),
            'paid_invoices' => $company->invoices()->where('status', 'paid')->count(),
            'overdue_invoices' => $company->invoices()->where('status', 'overdue')->count(),
            'cancelled_invoices' => $company->invoices()->where('status', 'cancelled')->count(),
            'total_revenue' => $company->invoices()->where('status', 'paid')->sum('total_amount'),
            'pending_revenue' => $company->invoices()->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
            'average_invoice_value' => $company->invoices()->where('status', 'paid')->avg('total_amount'),
        ];
    }

    /**
     * Get revenue by month for a company
     */
    public function getRevenueByMonth(int $companyId, int $year): array
    {
        $revenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $result[$month] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'total' => $revenue->get($month)?->total ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Search invoices with filters
     */
    public function search(int $companyId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Invoice::where('company_id', $companyId);

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['start_date'])) {
            $query->where('issue_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('issue_date', '<=', $filters['end_date']);
        }

        // Search by invoice number or client name
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Generate ANAF-compliant data (for future ANAF integration)
     */
    public function generateAnafData(Invoice $invoice): array
    {
        $company = $invoice->company;

        return [
            'furnizor' => [
                'cui' => $company->cui,
                'denumire' => $company->name,
                'reg_com' => $company->reg_com,
                'adresa' => $company->address,
            ],
            'client' => [
                'cui' => $invoice->client_cui,
                'denumire' => $invoice->client_name,
                'adresa' => $invoice->client_address,
            ],
            'factura' => [
                'numar' => $invoice->invoice_number,
                'serie' => $invoice->series,
                'data_emitere' => $invoice->issue_date->format('Y-m-d'),
                'data_scadenta' => $invoice->due_date->format('Y-m-d'),
                'valoare' => $invoice->amount,
                'tva' => $invoice->vat_amount,
                'total' => $invoice->total_amount,
                'moneda' => $invoice->currency,
            ],
            'linii' => $invoice->items,
        ];
    }
}
