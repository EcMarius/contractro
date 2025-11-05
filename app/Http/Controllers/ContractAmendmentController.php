<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAmendment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractAmendmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display amendments for a contract
     */
    public function index(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $amendments = $contract->amendments()
            ->latest('amendment_date')
            ->get();

        return view('theme::contracts.amendments.index', compact('contract', 'amendments'));
    }

    /**
     * Show the form for creating a new amendment
     */
    public function create(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Only allow amendments for signed/active contracts
        if (!in_array($contract->status, ['signed', 'active'])) {
            return redirect()->route('contracts.show', $contract->id)
                ->with('error', 'Puteți crea acte adiționale doar pentru contracte semnate sau active.');
        }

        return view('theme::contracts.amendments.create', compact('contract'));
    }

    /**
     * Store a newly created amendment
     */
    public function store(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $validated = $request->validate([
            'amendment_number' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amendment_date' => 'required|date',
            'effective_date' => 'nullable|date',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'amendment_number.required' => 'Numărul actului adițional este obligatoriu.',
            'title.required' => 'Titlul este obligatoriu.',
            'description.required' => 'Descrierea este obligatorie.',
            'amendment_date.required' => 'Data actului adițional este obligatorie.',
            'file.mimes' => 'Fișierul trebuie să fie în format PDF, DOC sau DOCX.',
            'file.max' => 'Fișierul nu poate depăși 10MB.',
        ]);

        try {
            DB::beginTransaction();

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('amendments', 'public');
            }

            $amendment = ContractAmendment::create([
                'contract_id' => $contract->id,
                'amendment_number' => $validated['amendment_number'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'amendment_date' => $validated['amendment_date'],
                'effective_date' => $validated['effective_date'] ?? null,
                'content' => $validated['content'] ?? null,
                'file_path' => $filePath,
            ]);

            DB::commit();

            \Log::info('Contract amendment created', [
                'user_id' => auth()->id(),
                'amendment_id' => $amendment->id,
                'contract_id' => $contract->id,
            ]);

            return redirect()->route('contracts.amendments.show', ['contract' => $contract->id, 'amendment' => $amendment->id])
                ->with('success', 'Actul adițional a fost creat cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create contract amendment', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la crearea actului adițional.');
        }
    }

    /**
     * Display the specified amendment
     */
    public function show(Contract $contract, ContractAmendment $amendment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify amendment belongs to this contract
        if ($amendment->contract_id !== $contract->id) {
            abort(404);
        }

        return view('theme::contracts.amendments.show', compact('contract', 'amendment'));
    }

    /**
     * Show the form for editing the specified amendment
     */
    public function edit(Contract $contract, ContractAmendment $amendment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify amendment belongs to this contract
        if ($amendment->contract_id !== $contract->id) {
            abort(404);
        }

        return view('theme::contracts.amendments.edit', compact('contract', 'amendment'));
    }

    /**
     * Update the specified amendment
     */
    public function update(Request $request, Contract $contract, ContractAmendment $amendment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($amendment->contract_id !== $contract->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amendment_number' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amendment_date' => 'required|date',
            'effective_date' => 'nullable|date',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // Handle file upload
            if ($request->hasFile('file')) {
                // Delete old file
                if ($amendment->file_path) {
                    Storage::disk('public')->delete($amendment->file_path);
                }

                $filePath = $request->file('file')->store('amendments', 'public');
                $validated['file_path'] = $filePath;
            }

            $amendment->update($validated);

            DB::commit();

            return redirect()->route('contracts.amendments.show', ['contract' => $contract->id, 'amendment' => $amendment->id])
                ->with('success', 'Actul adițional a fost actualizat cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea actului adițional.');
        }
    }

    /**
     * Remove the specified amendment
     */
    public function destroy(Contract $contract, ContractAmendment $amendment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($amendment->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            // Delete file
            if ($amendment->file_path) {
                Storage::disk('public')->delete($amendment->file_path);
            }

            $amendment->delete();

            DB::commit();

            return redirect()->route('contracts.amendments.index', $contract->id)
                ->with('success', 'Actul adițional a fost șters cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea actului adițional.');
        }
    }

    /**
     * Download amendment file
     */
    public function download(Contract $contract, ContractAmendment $amendment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($amendment->contract_id !== $contract->id) {
            abort(404);
        }

        if (!$amendment->file_path) {
            return redirect()->back()
                ->with('error', 'Acest act adițional nu are un fișier atașat.');
        }

        if (!Storage::disk('public')->exists($amendment->file_path)) {
            return redirect()->back()
                ->with('error', 'Fișierul nu a fost găsit.');
        }

        return Storage::disk('public')->download($amendment->file_path, $amendment->amendment_number . '.pdf');
    }
}
