<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display attachments for a contract
     */
    public function index(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $attachments = $contract->attachments()
            ->latest()
            ->get();

        return view('theme::contracts.attachments.index', compact('contract', 'attachments'));
    }

    /**
     * Show the form for uploading a new attachment
     */
    public function create(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        return view('theme::contracts.attachments.create', compact('contract'));
    }

    /**
     * Store a newly uploaded attachment
     */
    public function store(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'file.required' => 'Fișierul este obligatoriu.',
            'file.max' => 'Fișierul nu poate depăși 20MB.',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');

            // Store file
            $filePath = $file->store('contract-attachments', 'public');

            // Get file info
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            $attachment = ContractAttachment::create([
                'contract_id' => $contract->id,
                'user_id' => auth()->id(),
                'name' => $validated['name'] ?? $originalName,
                'description' => $validated['description'] ?? null,
                'file_path' => $filePath,
                'file_name' => $originalName,
                'file_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            DB::commit();

            \Log::info('Contract attachment uploaded', [
                'user_id' => auth()->id(),
                'attachment_id' => $attachment->id,
                'contract_id' => $contract->id,
            ]);

            return redirect()->route('contracts.attachments.index', $contract->id)
                ->with('success', 'Fișierul a fost încărcat cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to upload contract attachment', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la încărcarea fișierului.');
        }
    }

    /**
     * Display the specified attachment
     */
    public function show(Contract $contract, ContractAttachment $attachment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify attachment belongs to this contract
        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        return view('theme::contracts.attachments.show', compact('contract', 'attachment'));
    }

    /**
     * Show the form for editing the specified attachment
     */
    public function edit(Contract $contract, ContractAttachment $attachment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify attachment belongs to this contract
        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        return view('theme::contracts.attachments.edit', compact('contract', 'attachment'));
    }

    /**
     * Update the specified attachment metadata
     */
    public function update(Request $request, Contract $contract, ContractAttachment $attachment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Numele fișierului este obligatoriu.',
        ]);

        try {
            $attachment->update($validated);

            return redirect()->route('contracts.attachments.show', ['contract' => $contract->id, 'attachment' => $attachment->id])
                ->with('success', 'Fișierul a fost actualizat cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea fișierului.');
        }
    }

    /**
     * Remove the specified attachment
     */
    public function destroy(Contract $contract, ContractAttachment $attachment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            // Delete file from storage
            if ($attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachment->delete();

            DB::commit();

            return redirect()->route('contracts.attachments.index', $contract->id)
                ->with('success', 'Fișierul a fost șters cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea fișierului.');
        }
    }

    /**
     * Download attachment
     */
    public function download(Contract $contract, ContractAttachment $attachment)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($attachment->contract_id !== $contract->id) {
            abort(404);
        }

        if (!$attachment->file_path) {
            return redirect()->back()
                ->with('error', 'Fișierul nu are o cale asociată.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return redirect()->back()
                ->with('error', 'Fișierul nu a fost găsit pe server.');
        }

        try {
            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
        } catch (\Exception $e) {
            \Log::error('Failed to download attachment', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la descărcarea fișierului.');
        }
    }

    /**
     * Upload multiple attachments at once
     */
    public function bulkUpload(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:20480',
        ], [
            'files.required' => 'Vă rugăm să selectați cel puțin un fișier.',
            'files.max' => 'Puteți încărca maximum 10 fișiere deodată.',
            'files.*.max' => 'Fiecare fișier nu poate depăși 20MB.',
        ]);

        try {
            DB::beginTransaction();

            $uploaded = 0;

            foreach ($request->file('files') as $file) {
                $filePath = $file->store('contract-attachments', 'public');

                ContractAttachment::create([
                    'contract_id' => $contract->id,
                    'user_id' => auth()->id(),
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                $uploaded++;
            }

            DB::commit();

            return redirect()->route('contracts.attachments.index', $contract->id)
                ->with('success', "{$uploaded} fișiere au fost încărcate cu succes!");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'A apărut o eroare la încărcarea fișierelor.');
        }
    }
}
