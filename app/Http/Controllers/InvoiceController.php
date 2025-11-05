<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Contract;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->middleware('auth');
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            $firstCompany = Company::where('user_id', $user->id)->first();
            if (!$firstCompany) {
                return redirect()->route('companies.create')
                    ->with('info', 'Vă rugăm să creați mai întâi o companie.');
            }
            $companyId = $firstCompany->id;
            session(['active_company_id' => $companyId]);
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        // Build search filters
        $filters = [
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
        ];

        $query = Invoice::where('company_id', $companyId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('issue_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('issue_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('invoice_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('client_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('client_cui', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        $invoices = $query->with('contract')
            ->latest('issue_date')
            ->paginate(15);

        // Calculate stats
        $stats = [
            'total' => Invoice::where('company_id', $companyId)->count(),
            'draft' => Invoice::where('company_id', $companyId)->where('status', 'draft')->count(),
            'issued' => Invoice::where('company_id', $companyId)->where('status', 'issued')->count(),
            'paid' => Invoice::where('company_id', $companyId)->where('status', 'paid')->count(),
            'overdue' => Invoice::where('company_id', $companyId)->where('status', 'overdue')->count(),
            'total_revenue' => Invoice::where('company_id', $companyId)->where('status', 'paid')->sum('total_amount'),
            'pending_revenue' => Invoice::where('company_id', $companyId)->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
        ];

        $statuses = [
            'draft' => 'Ciornă',
            'issued' => 'Emisă',
            'paid' => 'Plătită',
            'overdue' => 'Restanță',
            'cancelled' => 'Anulată',
        ];

        return view('theme::invoices.index', compact('invoices', 'company', 'stats', 'statuses', 'filters'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create(Request $request)
    {
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Vă rugăm să selectați o companie.');
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        // Get contracts for dropdown
        $contracts = Contract::where('company_id', $companyId)
            ->where('status', 'active')
            ->with('contractParties')
            ->get();

        // Get contract_id from query parameter if creating from contract
        $contractId = $request->input('contract_id');
        $selectedContract = null;

        if ($contractId) {
            $selectedContract = Contract::with('contractParties')->find($contractId);
        }

        return view('theme::invoices.create', compact('company', 'contracts', 'selectedContract'));
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'series' => 'required|string|max:10',
            'client_name' => 'required|string|max:255',
            'client_cui' => 'nullable|string|max:50',
            'client_address' => 'nullable|string|max:500',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|max:3',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,issued,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'company_id.required' => 'Compania este obligatorie.',
            'client_name.required' => 'Numele clientului este obligatoriu.',
            'vat_rate.required' => 'Cota TVA este obligatorie.',
            'issue_date.required' => 'Data emiterii este obligatorie.',
            'due_date.required' => 'Data scadenței este obligatorie.',
            'due_date.after_or_equal' => 'Data scadenței trebuie să fie după data emiterii.',
            'items.required' => 'Factura trebuie să conțină cel puțin un articol.',
            'items.min' => 'Factura trebuie să conțină cel puțin un articol.',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $this->authorize('view', $company);

        try {
            $invoice = $this->invoiceService->create($validated);

            \Log::info('Invoice created successfully', [
                'user_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Factura a fost creată cu succes! Număr: ' . $invoice->invoice_number);

        } catch (\Exception $e) {
            \Log::error('Failed to create invoice', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la crearea facturii: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        $invoice->load(['contract', 'company']);

        // Check if overdue
        $isOverdue = $invoice->isOverdue();

        return view('theme::invoices.show', compact('invoice', 'isOverdue'));
    }

    /**
     * Show the form for editing the specified invoice
     */
    public function edit(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        // Only allow editing draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Puteți edita doar facturile în status de ciornă.');
        }

        $contracts = Contract::where('company_id', $invoice->company_id)
            ->where('status', 'active')
            ->get();

        return view('theme::invoices.edit', compact('invoice', 'contracts'));
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        // Only allow updating draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Puteți edita doar facturile în status de ciornă.');
        }

        $validated = $request->validate([
            'contract_id' => 'nullable|exists:contracts,id',
            'series' => 'required|string|max:10',
            'client_name' => 'required|string|max:255',
            'client_cui' => 'nullable|string|max:50',
            'client_address' => 'nullable|string|max:500',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|max:3',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $invoice = $this->invoiceService->update($invoice->id, $validated);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Factura a fost actualizată cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea facturii.');
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        // Only allow deleting draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Puteți șterge doar facturile în status de ciornă.');
        }

        try {
            $this->invoiceService->delete($invoice->id);

            return redirect()->route('invoices.index')
                ->with('success', 'Factura a fost ștearsă cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to delete invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea facturii.');
        }
    }

    /**
     * Issue invoice (change status from draft to issued)
     */
    public function issue(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Doar facturile în ciornă pot fi emise.');
        }

        try {
            $this->invoiceService->issue($invoice->id);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Factura a fost emisă cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la emiterea facturii.');
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        $validated = $request->validate([
            'payment_date' => 'required|date',
        ], [
            'payment_date.required' => 'Data plății este obligatorie.',
        ]);

        try {
            $this->invoiceService->markAsPaid($invoice->id, $validated['payment_date']);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Factura a fost marcată ca plătită!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la marcarea facturii ca plătită.');
        }
    }

    /**
     * Cancel invoice
     */
    public function cancel(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'Nu puteți anula o factură deja plătită.');
        }

        try {
            $this->invoiceService->cancel($invoice->id);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Factura a fost anulată!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la anularea facturii.');
        }
    }

    /**
     * Duplicate invoice
     */
    public function duplicate(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        try {
            $newInvoice = $this->invoiceService->duplicate($invoice->id);

            return redirect()->route('invoices.edit', $newInvoice->id)
                ->with('success', 'Factura a fost duplicată cu succes! Număr nou: ' . $newInvoice->invoice_number);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la duplicarea facturii.');
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadPdf(Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        try {
            // PDF generation will be implemented in Phase 8
            return redirect()->back()
                ->with('info', 'Descărcarea PDF va fi implementată în curând.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la generarea PDF-ului.');
        }
    }

    /**
     * Send invoice by email
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        $company = $invoice->company;
        $this->authorize('view', $company);

        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string',
        ], [
            'email.required' => 'Adresa de email este obligatorie.',
            'email.email' => 'Adresa de email nu este validă.',
        ]);

        try {
            // Email sending will be implemented in Phase 10
            return redirect()->back()
                ->with('info', 'Trimiterea de email-uri va fi implementată în curând.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la trimiterea email-ului.');
        }
    }

    /**
     * Create invoice from contract
     */
    public function createFromContract(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        return redirect()->route('invoices.create', [
            'company_id' => $company->id,
            'contract_id' => $contract->id,
        ]);
    }

    /**
     * Check for overdue invoices (AJAX)
     */
    public function checkOverdue(Company $company)
    {
        $this->authorize('view', $company);

        try {
            $updated = $this->invoiceService->checkOverdueInvoices($company->id);

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'message' => $updated > 0 ? "{$updated} facturi au fost marcate ca restante." : 'Nu sunt facturi restante.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare.',
            ], 500);
        }
    }
}
