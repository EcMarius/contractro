<?php

namespace App\Livewire;

use App\Services\LicenseService;
use Livewire\Component;

class LicenseChecker extends Component
{
    public string $domain = '';
    public ?array $result = null;
    public bool $checking = false;

    protected $rules = [
        'domain' => 'required|string|min:3',
    ];

    public function checkLicense()
    {
        $this->validate();

        $this->checking = true;
        $this->result = null;

        try {
            $licenseService = app(LicenseService::class);
            $this->result = $licenseService->checkDomainLicense($this->domain);
        } catch (\Exception $e) {
            $this->result = [
                'has_license' => false,
                'message' => 'Error checking license: ' . $e->getMessage(),
            ];
        } finally {
            $this->checking = false;
        }
    }

    public function resetForm()
    {
        $this->domain = '';
        $this->result = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.license-checker');
    }
}
