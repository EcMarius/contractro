<?php

namespace App\Livewire;

use App\Models\ContractTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateLibrary extends Component
{
    use WithPagination;

    public $search = '';
    public $category = 'all';
    public $showPublicOnly = true;
    public $previewTemplate = null;
    public $showPreviewModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function preview($templateId)
    {
        $this->previewTemplate = ContractTemplate::findOrFail($templateId);
        $this->showPreviewModal = true;
    }

    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->previewTemplate = null;
    }

    public function useTemplate($templateId)
    {
        return redirect()->route('contracts.create', ['template_id' => $templateId]);
    }

    public function getTemplatesProperty()
    {
        $query = ContractTemplate::query();

        // Filter by visibility
        if ($this->showPublicOnly) {
            $query->where(function ($q) {
                $q->where('is_public', true)
                  ->orWhere('user_id', auth()->id());
            });
        } else {
            $query->where('user_id', auth()->id());
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by category
        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        return $query->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->paginate(12);
    }

    public function getCategoriesProperty()
    {
        return ContractTemplate::where('is_public', true)
            ->orWhere('user_id', auth()->id())
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();
    }

    public function getStatsProperty()
    {
        return [
            'total' => ContractTemplate::where('is_public', true)
                ->orWhere('user_id', auth()->id())
                ->count(),
            'public' => ContractTemplate::where('is_public', true)->count(),
            'my_templates' => ContractTemplate::where('user_id', auth()->id())->count(),
        ];
    }

    public function render()
    {
        return view('livewire.template-library', [
            'templates' => $this->templates,
            'categories' => $this->categories,
            'stats' => $this->stats,
        ]);
    }
}
