<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display tasks for a contract
     */
    public function index(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $tasks = $contract->tasks()
            ->with('assignedUser')
            ->latest()
            ->get();

        // Group tasks by status
        $tasksByStatus = [
            'pending' => $tasks->where('status', 'pending'),
            'in_progress' => $tasks->where('status', 'in_progress'),
            'completed' => $tasks->where('status', 'completed'),
        ];

        return view('theme::contracts.tasks.index', compact('contract', 'tasks', 'tasksByStatus'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Get users who can be assigned tasks (organization members)
        $users = User::where('id', auth()->id())->get(); // For now, just current user

        return view('theme::contracts.tasks.create', compact('contract', 'users'));
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request, Contract $contract)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'assigned_to' => 'nullable|exists:users,id',
        ], [
            'title.required' => 'Titlul sarcinii este obligatoriu.',
            'priority.required' => 'Prioritatea este obligatorie.',
        ]);

        try {
            DB::beginTransaction();

            $task = ContractTask::create([
                'contract_id' => $contract->id,
                'created_by' => auth()->id(),
                'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'priority' => $validated['priority'],
                'status' => 'pending',
            ]);

            DB::commit();

            \Log::info('Contract task created', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'contract_id' => $contract->id,
            ]);

            return redirect()->route('contracts.tasks.index', $contract->id)
                ->with('success', 'Sarcina a fost creată cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create contract task', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la crearea sarcinii.');
        }
    }

    /**
     * Display the specified task
     */
    public function show(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify task belongs to this contract
        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        $task->load(['createdByUser', 'assignedUser']);

        return view('theme::contracts.tasks.show', compact('contract', 'task'));
    }

    /**
     * Show the form for editing the specified task
     */
    public function edit(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        // Verify task belongs to this contract
        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        $users = User::where('id', auth()->id())->get();

        return view('theme::contracts.tasks.edit', compact('contract', 'task', 'users'));
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        try {
            $task->update($validated);

            return redirect()->route('contracts.tasks.show', ['contract' => $contract->id, 'task' => $task->id])
                ->with('success', 'Sarcina a fost actualizată cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea sarcinii.');
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            $task->delete();

            return redirect()->route('contracts.tasks.index', $contract->id)
                ->with('success', 'Sarcina a fost ștearsă cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea sarcinii.');
        }
    }

    /**
     * Mark task as completed
     */
    public function complete(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            $task->complete();

            return redirect()->back()
                ->with('success', 'Sarcina a fost marcată ca finalizată!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la marcarea sarcinii ca finalizată.');
        }
    }

    /**
     * Mark task as in progress
     */
    public function start(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            $task->update(['status' => 'in_progress']);

            return redirect()->back()
                ->with('success', 'Sarcina a fost marcată ca în progres!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare.');
        }
    }

    /**
     * Reopen a completed task
     */
    public function reopen(Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        if ($task->status !== 'completed') {
            return redirect()->back()
                ->with('error', 'Doar sarcinile completate pot fi redeschise.');
        }

        try {
            $task->update([
                'status' => 'pending',
                'completed_at' => null,
            ]);

            return redirect()->back()
                ->with('success', 'Sarcina a fost redeschisă!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare.');
        }
    }

    /**
     * Assign task to user
     */
    public function assign(Request $request, Contract $contract, ContractTask $task)
    {
        $company = $contract->company;
        $this->authorize('view', $company);

        if ($task->contract_id !== $contract->id) {
            abort(404);
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ], [
            'assigned_to.required' => 'Vă rugăm să selectați un utilizator.',
        ]);

        try {
            $task->update(['assigned_to' => $validated['assigned_to']]);

            return redirect()->back()
                ->with('success', 'Sarcina a fost atribuită cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la atribuirea sarcinii.');
        }
    }

    /**
     * Get my tasks (all tasks assigned to current user)
     */
    public function myTasks(Request $request)
    {
        $user = auth()->user();

        $filter = $request->input('filter', 'all'); // all, pending, in_progress, completed

        $query = ContractTask::where('assigned_to', $user->id)
            ->with(['contract.company']);

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $tasks = $query->latest()->paginate(20);

        return view('theme::tasks.my-tasks', compact('tasks', 'filter'));
    }

    /**
     * Get overdue tasks
     */
    public function overdue(Request $request)
    {
        $user = auth()->user();

        $tasks = ContractTask::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with(['contract.company'])
            ->latest('due_date')
            ->paginate(20);

        return view('theme::tasks.overdue', compact('tasks'));
    }
}
