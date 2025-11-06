<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractComment;
use App\Services\ContractPDFService;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractViewer extends Component
{
    use AuthorizesRequests;

    public $contractId;
    public $contract;
    public $activeTab = 'content';
    public $newComment = '';
    public $showSignatureModal = false;

    protected $rules = [
        'newComment' => 'required|string|min:3|max:1000',
    ];

    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->loadContract();
        $this->authorize('view', $this->contract);
    }

    public function loadContract()
    {
        $this->contract = Contract::with([
            'user',
            'template',
            'signatures' => function ($query) {
                $query->orderBy('signing_order');
            },
            'signatures.user',
            'versions' => function ($query) {
                $query->orderBy('version_number', 'desc')->limit(10);
            },
            'versions.changedBy',
            'comments' => function ($query) {
                $query->whereNull('parent_id')->with('user', 'replies.user')->orderBy('created_at', 'desc');
            },
            'comments.user',
        ])->findOrFail($this->contractId);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function addComment()
    {
        $this->validate();

        ContractComment::create([
            'contract_id' => $this->contract->id,
            'user_id' => auth()->id(),
            'comment' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->loadContract();
        session()->flash('message', 'Comment added successfully.');
    }

    public function downloadPDF()
    {
        $this->authorize('downloadPdf', $this->contract);

        $pdfService = app(ContractPDFService::class);
        return $pdfService->downloadPDF($this->contract);
    }

    public function sendForSignature()
    {
        $this->authorize('sendForSignature', $this->contract);
        $this->showSignatureModal = true;
    }

    public function cancelSignatureModal()
    {
        $this->showSignatureModal = false;
    }

    public function getProgressPercentageProperty()
    {
        $totalSignatures = $this->contract->signatures->count();
        if ($totalSignatures === 0) {
            return 0;
        }

        $signedCount = $this->contract->signatures->where('status', 'signed')->count();
        return round(($signedCount / $totalSignatures) * 100);
    }

    public function render()
    {
        return view('livewire.contract-viewer', [
            'progressPercentage' => $this->progressPercentage,
        ]);
    }
}
