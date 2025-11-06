<?php

namespace App\Livewire;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractDashboard extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $statusFilter = 'all';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showDeleteModal = false;
    public $contractToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->authorize('viewAny', Contract::class);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function confirmDelete($contractId)
    {
        $this->contractToDelete = $contractId;
        $this->showDeleteModal = true;
    }

    public function deleteContract()
    {
        $contract = Contract::findOrFail($this->contractToDelete);
        $this->authorize('delete', $contract);

        $contract->delete();

        $this->showDeleteModal = false;
        $this->contractToDelete = null;

        session()->flash('message', 'Contract deleted successfully.');
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->contractToDelete = null;
    }

    public function getContractsProperty()
    {
        $query = Contract::query()
            ->with(['template', 'signatures', 'user'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id())
                  ->orWhereHas('signatures', function ($sq) {
                      $sq->where('user_id', auth()->id());
                  });
            });

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(10);
    }

    public function getStatsProperty()
    {
        $userId = auth()->id();

        return [
            'total' => Contract::where('user_id', $userId)
                ->orWhere('created_by', $userId)
                ->count(),
            'draft' => Contract::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('created_by', $userId);
            })->where('status', 'draft')->count(),
            'pending' => Contract::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('created_by', $userId);
            })->whereIn('status', ['pending_signature', 'partially_signed'])->count(),
            'signed' => Contract::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('created_by', $userId);
            })->where('status', 'signed')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.contract-dashboard', [
            'contracts' => $this->contracts,
            'stats' => $this->stats,
        ]);
    }
}
