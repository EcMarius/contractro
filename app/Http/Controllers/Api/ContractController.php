<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Services\ContractService;
use App\Services\ContractPDFService;
use App\Services\AIContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    protected $contractService;
    protected $pdfService;
    protected $aiService;

    public function __construct(
        ContractService $contractService,
        ContractPDFService $pdfService,
        AIContractService $aiService
    ) {
        $this->contractService = $contractService;
        $this->pdfService = $pdfService;
        $this->aiService = $aiService;
    }

    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {
        $query = Contract::with(['user', 'template', 'signatures', 'creator', 'organization'])
            ->where('user_id', auth()->id());

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('contract_number', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $contracts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'template_id' => 'nullable|exists:contract_templates,id',
            'description' => 'nullable|string',
            'content' => 'required_without:template_id|string',
            'variables' => 'nullable|array',
            'contract_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'organization_id' => 'nullable|exists:organizations,id',
            'lead_id' => 'nullable|exists:leads,id',
            'effective_date' => 'nullable|date',
            'expires_at' => 'nullable|date|after:effective_date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->filled('template_id')) {
                $template = ContractTemplate::findOrFail($request->template_id);
                $contract = $this->contractService->createFromTemplate($template, $request->all());
            } else {
                $contract = Contract::create(array_merge(
                    $request->all(),
                    ['user_id' => auth()->id(), 'created_by' => auth()->id()]
                ));
                $contract->createVersion('Initial contract created');
            }

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => $contract->load(['template', 'signatures', 'versions']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified contract
     */
    public function show($id)
    {
        $contract = Contract::with([
            'user',
            'template',
            'signatures.user',
            'versions.changedBy',
            'comments.user',
            'creator',
            'organization',
            'lead'
        ])->findOrFail($id);

        // Check authorization
        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this contract',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        // Check authorization
        if (!$contract->canBeEditedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this contract',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'variables' => 'nullable|array',
            'status' => 'sometimes|in:draft,pending_signature,partially_signed,signed,completed,cancelled,expired',
            'contract_value' => 'nullable|numeric|min:0',
            'effective_date' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'change_summary' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $contract = $this->contractService->updateContract($contract, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully',
                'data' => $contract->load(['template', 'signatures', 'versions']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified contract
     */
    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);

        // Check authorization
        if ($contract->user_id !== auth()->id() && $contract->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this contract',
            ], 403);
        }

        // Don't allow deletion of signed contracts
        if (in_array($contract->status, ['signed', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete signed or completed contracts',
            ], 422);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }

    /**
     * Duplicate a contract
     */
    public function duplicate($id, Request $request)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to duplicate this contract',
            ], 403);
        }

        try {
            $newContract = $this->contractService->duplicate($contract, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Contract duplicated successfully',
                'data' => $newContract,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send contract for signature
     */
    public function sendForSignature($id, Request $request)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeEditedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to send this contract for signature',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'signers' => 'required|array|min:1',
            'signers.*.name' => 'required|string',
            'signers.*.email' => 'required|email',
            'signers.*.role' => 'nullable|string',
            'signers.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $contract = $this->contractService->sendForSignature($contract, $request->signers);

            return response()->json([
                'success' => true,
                'message' => 'Contract sent for signature successfully',
                'data' => $contract->load('signatures'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download contract as PDF
     */
    public function downloadPDF($id)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to download this contract',
            ], 403);
        }

        try {
            return $this->pdfService->downloadPDF($contract);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get contract statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->contractService->getUserStatistics(auth()->user());

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI: Generate contract from description
     */
    public function generateFromAI(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|min:10',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $generated = $this->aiService->generateFromDescription(
                $request->description,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Contract generated successfully',
                'data' => $generated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI: Review contract
     */
    public function reviewWithAI($id)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to review this contract',
            ], 403);
        }

        try {
            $review = $this->aiService->reviewContract($contract);

            return response()->json([
                'success' => true,
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to review contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI: Suggest improvements
     */
    public function suggestImprovements($id)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $suggestions = $this->aiService->suggestImprovements($contract);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate suggestions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI: Extract key points
     */
    public function extractKeyPoints($id)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $keyPoints = $this->aiService->extractKeyPoints($contract);

            return response()->json([
                'success' => true,
                'data' => $keyPoints,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract key points: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get contract versions
     */
    public function versions($id)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeViewedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $versions = $contract->versions()->with('changedBy')->get();

        return response()->json([
            'success' => true,
            'data' => $versions,
        ]);
    }

    /**
     * Restore contract to a specific version
     */
    public function restoreVersion($id, $versionId)
    {
        $contract = Contract::findOrFail($id);

        if (!$contract->canBeEditedBy(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $version = $contract->versions()->findOrFail($versionId);
        $version->restore();

        return response()->json([
            'success' => true,
            'message' => 'Version restored successfully',
            'data' => $contract->fresh(),
        ]);
    }
}
