<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Company;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContractApiController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->middleware('auth:sanctum');
        $this->contractService = $contractService;
    }

    /**
     * Get all contracts for user's companies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $request->input('company_id');

            if ($companyId) {
                $company = Company::findOrFail($companyId);
                if ($company->user_id !== $user->id) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                $contracts = Contract::where('company_id', $companyId);
            } else {
                // Get all contracts from user's companies
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
                $contracts = Contract::whereIn('company_id', $companyIds);
            }

            // Apply filters
            if ($request->has('status')) {
                $contracts->where('status', $request->input('status'));
            }

            if ($request->has('contract_type_id')) {
                $contracts->where('contract_type_id', $request->input('contract_type_id'));
            }

            $contracts = $contracts->with(['contractType', 'contractParties', 'company'])
                ->latest()
                ->paginate(20);

            return response()->json($contracts);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch contracts', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific contract
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $contract = Contract::with([
                'contractType',
                'contractParties.signatures',
                'amendments',
                'attachments',
                'tasks',
                'invoices',
                'company'
            ])->findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json([
                'contract' => $contract,
                'is_fully_signed' => $contract->isFullySigned(),
                'signing_progress' => [
                    'total_parties' => $contract->contractParties->count(),
                    'signed_parties' => $contract->contractParties->filter(fn($p) => $p->hasSigned())->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Contract not found', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Create a new contract
     */
    public function store(Request $request): JsonResponse
    {
        try {
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
            ]);

            // Check authorization
            $company = Company::findOrFail($validated['company_id']);
            if ($company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $contract = $this->contractService->create($validated);

            return response()->json([
                'message' => 'Contract created successfully',
                'contract' => $contract->load(['contractType', 'contractParties']),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create contract', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a contract
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only allow updating draft contracts
            if ($contract->status !== 'draft') {
                return response()->json(['error' => 'Only draft contracts can be updated'], 400);
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

            $contract = $this->contractService->update($id, $validated);

            return response()->json([
                'message' => 'Contract updated successfully',
                'contract' => $contract->load(['contractType', 'contractParties']),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update contract', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a contract
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only allow deleting draft contracts
            if ($contract->status !== 'draft') {
                return response()->json(['error' => 'Only draft contracts can be deleted'], 400);
            }

            $this->contractService->delete($id);

            return response()->json(['message' => 'Contract deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete contract', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send contract for signing
     */
    public function sendForSigning(Request $request, int $id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($contract->status !== 'draft') {
                return response()->json(['error' => 'Only draft contracts can be sent for signing'], 400);
            }

            $results = $this->contractService->sendForSigning($contract);

            return response()->json([
                'message' => 'Contract sent for signing successfully',
                'signing_links' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send contract for signing', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel contract
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $this->contractService->cancel($id);

            return response()->json(['message' => 'Contract cancelled successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cancel contract', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Duplicate contract
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Check authorization
            if ($contract->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $newContract = $this->contractService->duplicate($id);

            return response()->json([
                'message' => 'Contract duplicated successfully',
                'contract' => $newContract->load(['contractType', 'contractParties']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to duplicate contract', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get contract statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $request->input('company_id');

            if ($companyId) {
                $company = Company::findOrFail($companyId);
                if ($company->user_id !== $user->id) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
                $companyIds = [$companyId];
            } else {
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
            }

            $stats = [
                'total' => Contract::whereIn('company_id', $companyIds)->count(),
                'by_status' => [
                    'draft' => Contract::whereIn('company_id', $companyIds)->where('status', 'draft')->count(),
                    'pending' => Contract::whereIn('company_id', $companyIds)->where('status', 'pending')->count(),
                    'signed' => Contract::whereIn('company_id', $companyIds)->where('status', 'signed')->count(),
                    'active' => Contract::whereIn('company_id', $companyIds)->where('status', 'active')->count(),
                    'expired' => Contract::whereIn('company_id', $companyIds)->where('status', 'expired')->count(),
                    'terminated' => Contract::whereIn('company_id', $companyIds)->where('status', 'terminated')->count(),
                ],
                'total_value' => Contract::whereIn('company_id', $companyIds)->where('status', 'active')->sum('value'),
                'expiring_soon' => Contract::whereIn('company_id', $companyIds)
                    ->where('status', 'active')
                    ->whereNotNull('end_date')
                    ->whereBetween('end_date', [now(), now()->addDays(30)])
                    ->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics', 'message' => $e->getMessage()], 500);
        }
    }
}
