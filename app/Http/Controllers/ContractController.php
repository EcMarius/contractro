<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Company;
use App\Models\ContractType;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContractController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->middleware('auth');
        $this->contractService = $contractService;
    }

    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            $firstCompany = Company::where('user_id', $user->id)->first();
            if (!$firstCompany) {
                return redirect()->route('companies.create')
                    ->with('info', 'Vă rugăm să creați mai întâi o companie pentru a gestiona contracte.');
            }
            $companyId = $firstCompany->id;
            session(['active_company_id' => $companyId]);
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        // Build search filters
        $filters = [
            'status' => $request->input('status'),
            'contract_type_id' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
        ];

        $contracts = $this->contractService->search($companyId, $filters)
            ->with(['contractType', 'contractParties'])
            ->latest()
            ->paginate(15);

        $contractTypes = ContractType::all();
        $statuses = [
            'draft' => 'Ciornă',
            'pending' => 'Așteptare semnătură',
            'signed' => 'Semnat',
            'active' => 'Activ',
            'expired' => 'Expirat',
            'terminated' => 'Reziliat',
        ];

        return view('theme::contracts.index', compact('contracts', 'company', 'contractTypes', 'statuses', 'filters'));
    }

    /**
     * Show the form for creating a new contract
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

        $contractTypes = ContractType::all();

        return view('theme::contracts.create', compact('company', 'contractTypes'));
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'contract_type_id' => 'required|exists:contract_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,pending,signed,active,expired,terminated',
            'signing_method' => 'required|in:sms,handwritten,digital',
            'content' => 'nullable|string',
            'parties' => 'nullable|array',
            'parties.*.party_type' => 'required|in:client,provider,witness,other',
            'parties.*.name' => 'required|string|max:255',
            'parties.*.email' => 'nullable|email',
            'parties.*.phone' => 'nullable|string|max:50',
            'parties.*.company_name' => 'nullable|string|max:255',
            'parties.*.company_cui' => 'nullable|string|max:50',
        ], [
            'company_id.required' => 'Compania este obligatorie.',
            'contract_type_id.required' => 'Tipul de contract este obligatoriu.',
            'title.required' => 'Titlul contractului este obligatoriu.',
            'end_date.after' => 'Data de încheiere trebuie să fie după data de început.',
            'value.numeric' => 'Valoarea trebuie să fie un număr.',
            'parties.*.name.required' => 'Numele părții este obligatoriu.',
            'parties.*.email.email' => 'Email-ul trebuie să fie valid.',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $this->authorize('view', $company);

        try {
            $contract = $this->contractService->create($validated);

            \Log::info('Contract created successfully', [
                'user_id' => auth()->id(),
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Contractul a fost creat cu succes! Număr: ' . $contract->contract_number);

        } catch (\Exception $e) {
            \Log::error('Failed to create contract', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la crearea contractului: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contract
     */
    public function show(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $contract->load([
            'contractType',
            'contractParties.signatures',
            'amendments',
            'attachments',
            'tasks',
            'notes',
            'invoices',
        ]);

        // Get signing progress
        $signingProgress = [
            'total_parties' => $contract->contractParties->count(),
            'signed_parties' => $contract->contractParties->filter(fn($p) => $p->hasSigned())->count(),
            'is_fully_signed' => $contract->isFullySigned(),
        ];

        return view('theme::contracts.show', compact('contract', 'signingProgress'));
    }

    /**
     * Show the form for editing the specified contract
     */
    public function edit(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Only allow editing draft contracts
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți edita doar contractele în status de ciornă.');
        }

        $contractTypes = ContractType::all();

        return view('theme::contracts.edit', compact('contract', 'contractTypes'));
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Only allow updating draft contracts
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți edita doar contractele în status de ciornă.');
        }

        $validated = $request->validate([
            'contract_type_id' => 'required|exists:contract_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'content' => 'nullable|string',
        ]);

        try {
            $contract = $this->contractService->update($contract->id, $validated);

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Contractul a fost actualizat cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to update contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea contractului.');
        }
    }

    /**
     * Remove the specified contract
     */
    public function destroy(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Only allow deleting draft contracts
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți șterge doar contractele în status de ciornă.');
        }

        try {
            $this->contractService->delete($contract->id);

            return redirect()->route('contracts.index')
                ->with('success', 'Contractul a fost șters cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to delete contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea contractului.');
        }
    }

    /**
     * Send contract for signing
     */
    public function sendForSigning(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Doar contractele în status de ciornă pot fi trimise spre semnare.');
        }

        // Check if contract has parties
        if ($contract->contractParties->count() === 0) {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Contractul trebuie să aibă cel puțin o parte pentru a fi trimis spre semnare.');
        }

        try {
            $results = $this->contractService->sendForSigning($contract);

            // Here we would send emails/SMS with signing links
            // For now, just show the links in the success message

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Contractul a fost trimis spre semnare către ' . count($results) . ' părți.')
                ->with('signing_links', $results);

        } catch (\Exception $e) {
            \Log::error('Failed to send contract for signing', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la trimiterea contractului spre semnare.');
        }
    }

    /**
     * Cancel contract
     */
    public function cancel(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if (!in_array($contract->status, ['pending', 'signed', 'active'])) {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Acest contract nu poate fi anulat.');
        }

        try {
            $this->contractService->cancel($contract->id);

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Contractul a fost anulat cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la anularea contractului.');
        }
    }

    /**
     * Duplicate contract
     */
    public function duplicate(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        try {
            $newContract = $this->contractService->duplicate($contract->id);

            return redirect()->route('contracts.edit', $newContract->id)
                ->with('success', 'Contractul a fost duplicat cu succes! Număr nou: ' . $newContract->contract_number);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la duplicarea contractului.');
        }
    }

    /**
     * Download contract PDF
     */
    public function downloadPdf(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        try {
            // PDF generation will be implemented in Phase 8
            // For now, return a placeholder

            return redirect()->back()
                ->with('info', 'Descărcarea PDF va fi implementată în curând.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la generarea PDF-ului.');
        }
    }

    /**
     * Add party to contract
     */
    public function addParty(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți adăuga părți doar la contractele în status de ciornă.');
        }

        $validated = $request->validate([
            'party_type' => 'required|in:client,provider,witness,other',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'company_cui' => 'nullable|string|max:50',
        ]);

        try {
            $contract->addParty($validated);

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Partea a fost adăugată cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la adăugarea părții.');
        }
    }

    /**
     * Remove party from contract
     */
    public function removeParty(Contract $contract, int $partyId)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți elimina părți doar din contractele în status de ciornă.');
        }

        try {
            $contract->contractParties()->where('id', $partyId)->delete();

            return redirect()->route('contracts.show', $contract->id)
                ->with('success', 'Partea a fost eliminată cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la eliminarea părții.');
        }
    }
}
