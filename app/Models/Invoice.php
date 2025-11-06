<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'contract_id',
        'invoice_number',
        'series',
        'client_name',
        'client_cui',
        'client_address',
        'amount',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'currency',
        'issue_date',
        'due_date',
        'status',
        'payment_date',
        'items',
        'notes',
        // ANAF e-Factura fields
        'anaf_upload_index',
        'anaf_status',
        'anaf_uploaded_at',
        'anaf_validated_at',
        'anaf_response',
        'anaf_error',
    ];

    protected $casts = [
        'items' => 'array',
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'anaf_uploaded_at' => 'datetime',
        'anaf_validated_at' => 'datetime',
        'anaf_response' => 'array',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Generate invoice number
    public static function generateInvoiceNumber(int $companyId, string $series = 'FACT'): string
    {
        $year = now()->year;
        $lastInvoice = self::where('company_id', $companyId)
            ->where('series', $series)
            ->whereYear('issue_date', $year)
            ->orderBy('invoice_number', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastInvoice) {
            // Extract number from format like "FACT-0001/2025"
            preg_match('/(\d+)/', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
        }

        $newNumber = $lastNumber + 1;
        return sprintf('%s-%04d/%s', $series, $newNumber, $year);
    }

    // Calculate totals from items
    public function calculateTotals(): void
    {
        $subtotal = 0;

        foreach ($this->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $this->amount = $subtotal;
        $this->vat_amount = $subtotal * ($this->vat_rate / 100);
        $this->total_amount = $subtotal + $this->vat_amount;
    }

    // Mark as paid
    public function markAsPaid(?string $paymentDate = null): bool
    {
        return $this->update([
            'status' => 'paid',
            'payment_date' => $paymentDate ?? now(),
        ]);
    }

    // Check if overdue
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    // Get status label
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Ciornă',
            'issued' => 'Emisă',
            'paid' => 'Plătită',
            'overdue' => 'Restanță',
            'cancelled' => 'Anulată',
            default => 'Necunoscut',
        };
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'issued' => 'blue',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    // Romanian invoice format validation
    public function validateRomanianFormat(): array
    {
        $errors = [];

        if (empty($this->series)) {
            $errors[] = 'Serie factură este obligatorie';
        }

        if (empty($this->client_name)) {
            $errors[] = 'Nume client este obligatoriu';
        }

        if (empty($this->items) || count($this->items) === 0) {
            $errors[] = 'Factura trebuie să conțină cel puțin un articol';
        }

        if ($this->vat_rate < 0 || $this->vat_rate > 100) {
            $errors[] = 'Cota TVA invalidă';
        }

        return $errors;
    }

    // Add item to invoice
    public function addItem(string $description, float $quantity, float $unitPrice, string $unit = 'buc'): void
    {
        $items = $this->items ?? [];
        $items[] = [
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ];

        $this->items = $items;
        $this->calculateTotals();
        $this->save();
    }
}
