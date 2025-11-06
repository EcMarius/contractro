<?php

namespace App\Livewire;

use App\Models\ContractSignature;
use Livewire\Component;

class ContractSignaturePad extends Component
{
    public $token;
    public $signature;
    public $contract;
    public $signatureData = null;
    public $signatureType = 'drawn';
    public $typedSignature = '';
    public $agreedToTerms = false;
    public $declined = false;
    public $declineReason = '';

    protected $rules = [
        'agreedToTerms' => 'accepted',
        'declineReason' => 'required_if:declined,true|string|max:500',
    ];

    public function mount($token)
    {
        $this->token = $token;
        $this->loadSignature();
    }

    public function loadSignature()
    {
        $this->signature = ContractSignature::where('verification_token', $this->token)
            ->with('contract')
            ->firstOrFail();

        $this->contract = $this->signature->contract;

        // Check if expired
        if ($this->signature->isExpired()) {
            session()->flash('error', 'This signature request has expired.');
        }

        // Check if already signed
        if ($this->signature->status === 'signed') {
            session()->flash('info', 'You have already signed this contract.');
        }
    }

    public function setSignatureType($type)
    {
        $this->signatureType = $type;
        $this->signatureData = null;
        $this->typedSignature = '';
    }

    public function sign()
    {
        if ($this->signature->status === 'signed') {
            session()->flash('error', 'This contract has already been signed.');
            return;
        }

        if ($this->signature->isExpired()) {
            session()->flash('error', 'This signature request has expired.');
            return;
        }

        $this->validate();

        // Prepare signature data based on type
        $signatureData = $this->signatureType === 'typed'
            ? $this->typedSignature
            : $this->signatureData;

        if (empty($signatureData)) {
            session()->flash('error', 'Please provide your signature.');
            return;
        }

        // Sign the contract
        $this->signature->sign($signatureData, $this->signatureType);

        session()->flash('message', 'Contract signed successfully!');
        $this->loadSignature();
    }

    public function decline()
    {
        if ($this->signature->status !== 'pending') {
            session()->flash('error', 'This signature request is no longer pending.');
            return;
        }

        $this->declined = true;
        $this->validate(['declineReason' => 'required|string|max:500']);

        $this->signature->decline($this->declineReason);

        session()->flash('message', 'You have declined to sign this contract.');
        $this->loadSignature();
    }

    public function clearSignature()
    {
        $this->signatureData = null;
        $this->typedSignature = '';
    }

    public function render()
    {
        return view('livewire.contract-signature-pad');
    }
}
