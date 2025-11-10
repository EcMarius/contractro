<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Services\AIContractService;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractEditor extends Component
{
    use AuthorizesRequests;

    public $contractId;
    public $contract;
    public $title = '';
    public $content = '';
    public $variables = [];
    public $templateId = null;
    public $contractValue;
    public $effectiveDate;
    public $expirationDate;
    public $showAIPanel = false;
    public $aiPrompt = '';
    public $aiLoading = false;
    public $showVariablesPanel = false;
    public $availableVariables = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'contractValue' => 'nullable|numeric|min:0',
        'effectiveDate' => 'nullable|date',
        'expirationDate' => 'nullable|date|after:effective_date',
    ];

    public function mount($contractId = null, $templateId = null)
    {
        if ($contractId) {
            $this->contract = Contract::with('template')->findOrFail($contractId);
            $this->authorize('update', $this->contract);

            $this->contractId = $this->contract->id;
            $this->title = $this->contract->title;
            $this->content = $this->contract->content;
            $this->variables = $this->contract->variables ?? [];
            $this->contractValue = $this->contract->contract_value;
            $this->effectiveDate = $this->contract->effective_date?->format('Y-m-d');
            $this->expirationDate = $this->contract->expiration_date?->format('Y-m-d');
            $this->templateId = $this->contract->template_id;
        } elseif ($templateId) {
            $template = ContractTemplate::findOrFail($templateId);
            $this->authorize('create', Contract::class);

            $this->title = $template->name;
            $this->content = $template->content;
            $this->variables = $template->variables ?? [];
            $this->templateId = $template->id;
        } else {
            $this->authorize('create', Contract::class);
        }

        $this->extractAvailableVariables();
    }

    public function extractAvailableVariables()
    {
        // Extract {{variable_name}} patterns from content
        preg_match_all('/\{\{(\w+)\}\}/', $this->content, $matches);

        $this->availableVariables = array_unique($matches[1]);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'variables' => $this->variables,
            'contract_value' => $this->contractValue,
            'effective_date' => $this->effectiveDate,
            'expiration_date' => $this->expirationDate,
        ];

        if ($this->contractId) {
            // Update existing contract
            $this->contract->update($data);

            // Create version if content changed
            if ($this->contract->wasChanged('content')) {
                $this->contract->createVersion('Content updated via editor');
            }

            session()->flash('message', 'Contract updated successfully.');
        } else {
            // Create new contract
            $data['template_id'] = $this->templateId;
            $data['user_id'] = auth()->id();
            $data['created_by'] = auth()->id();
            $data['status'] = 'draft';

            $this->contract = Contract::create($data);
            $this->contractId = $this->contract->id;

            session()->flash('message', 'Contract created successfully.');
            return redirect()->route('contracts.edit', $this->contract->id);
        }
    }

    public function saveAndContinue()
    {
        $this->save();
        return redirect()->route('contracts.show', $this->contract->id);
    }

    public function insertVariable($variable)
    {
        $this->content .= ' {{' . $variable . '}}';
        $this->extractAvailableVariables();
    }

    public function toggleAIPanel()
    {
        if (!$this->canUseAI()) {
            session()->flash('error', 'AI features are not available in your plan.');
            return;
        }

        $this->showAIPanel = !$this->showAIPanel;
    }

    public function generateWithAI()
    {
        if (!$this->canUseAI()) {
            session()->flash('error', 'AI features are not available in your plan.');
            return;
        }

        $this->validate([
            'aiPrompt' => 'required|string|min:10',
        ]);

        $this->aiLoading = true;

        try {
            $aiService = app(AIContractService::class);

            $result = $aiService->generateFromDescription($this->aiPrompt, [
                'title' => $this->title,
                'existing_content' => $this->content,
            ]);

            if (!empty($result['content'])) {
                $this->content = $result['content'];
                $this->title = $result['title'] ?? $this->title;
                $this->extractAvailableVariables();

                session()->flash('message', 'AI-generated content added successfully.');
            }

            $this->aiPrompt = '';
            $this->showAIPanel = false;
        } catch (\Exception $e) {
            session()->flash('error', 'AI generation failed: ' . $e->getMessage());
        } finally {
            $this->aiLoading = false;
        }
    }

    public function improveWithAI()
    {
        if (!$this->canUseAI()) {
            session()->flash('error', 'AI features are not available in your plan.');
            return;
        }

        if (empty($this->content)) {
            session()->flash('error', 'Please add some content first.');
            return;
        }

        $this->aiLoading = true;

        try {
            $aiService = app(AIContractService::class);

            $improved = $aiService->simplifyLanguage($this->content);
            $this->content = $improved;

            session()->flash('message', 'Content improved with AI successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'AI improvement failed: ' . $e->getMessage());
        } finally {
            $this->aiLoading = false;
        }
    }

    public function canUseAI()
    {
        $plan = auth()->user()->subscription('default')?->plan;
        return $plan && ($plan->enable_ai_contract_generation ?? false);
    }

    public function render()
    {
        return view('livewire.contract-editor');
    }
}
