<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Company;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceApiController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->middleware('auth:sanctum');
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get all invoices for user's companies
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

                $invoices = Invoice::where('company_id', $companyId);
            } else {
                // Get all invoices from user's companies
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
                $invoices = Invoice::whereIn('company_id', $companyIds);
            }

            // Apply filters
            if ($request->has('status')) {
                $invoices->where('status', $request->input('status'));
            }

            if ($request->has('start_date')) {
                $invoices->where('issue_date', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $invoices->where('issue_date', '<=', $request->input('end_date'));
            }

            $invoices = $invoices->with(['contract', 'company'])
                ->latest('issue_date')
                ->paginate(20);

            return response()->json($invoices);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch invoices', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific invoice
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $invoice = Invoice::with(['contract', 'company'])->findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json([
                'invoice' => $invoice,
                'is_overdue' => $invoice->isOverdue(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Invoice not found', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Create a new invoice
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'contract_id' => 'nullable|exists:contracts,id',
                'series' => 'required|string|max:10',
                'client_name' => 'required|string|max:255',
                'client_cui' => 'nullable|string|max:50',
                'client_address' => 'nullable|string|max:500',
                'vat_rate' => 'required|numeric|min:0|max:100',
                'currency' => 'required|string|max:3',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'status' => 'required|in:draft,issued,paid,overdue,cancelled',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string|max:20',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            // Check authorization
            $company = Company::findOrFail($validated['company_id']);
            if ($company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $invoice = $this->invoiceService->create($validated);

            return response()->json([
                'message' => 'Invoice created successfully',
                'invoice' => $invoice->load('contract'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an invoice
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only allow updating draft invoices
            if ($invoice->status !== 'draft') {
                return response()->json(['error' => 'Only draft invoices can be updated'], 400);
            }

            $validated = $request->validate([
                'contract_id' => 'nullable|exists:contracts,id',
                'series' => 'required|string|max:10',
                'client_name' => 'required|string|max:255',
                'client_cui' => 'nullable|string|max:50',
                'client_address' => 'nullable|string|max:500',
                'vat_rate' => 'required|numeric|min:0|max:100',
                'currency' => 'required|string|max:3',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
            ]);

            $invoice = $this->invoiceService->update($id, $validated);

            return response()->json([
                'message' => 'Invoice updated successfully',
                'invoice' => $invoice->load('contract'),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an invoice
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only allow deleting draft invoices
            if ($invoice->status !== 'draft') {
                return response()->json(['error' => 'Only draft invoices can be deleted'], 400);
            }

            $this->invoiceService->delete($id);

            return response()->json(['message' => 'Invoice deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Issue invoice
     */
    public function issue(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($invoice->status !== 'draft') {
                return response()->json(['error' => 'Only draft invoices can be issued'], 400);
            }

            $this->invoiceService->issue($id);

            return response()->json(['message' => 'Invoice issued successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to issue invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'payment_date' => 'required|date',
            ]);

            $this->invoiceService->markAsPaid($id, $validated['payment_date']);

            return response()->json(['message' => 'Invoice marked as paid successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark invoice as paid', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel invoice
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($invoice->status === 'paid') {
                return response()->json(['error' => 'Cannot cancel a paid invoice'], 400);
            }

            $this->invoiceService->cancel($id);

            return response()->json(['message' => 'Invoice cancelled successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cancel invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Duplicate invoice
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->company->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $newInvoice = $this->invoiceService->duplicate($id);

            return response()->json([
                'message' => 'Invoice duplicated successfully',
                'invoice' => $newInvoice->load('contract'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to duplicate invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get invoice statistics
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
                'total' => Invoice::whereIn('company_id', $companyIds)->count(),
                'by_status' => [
                    'draft' => Invoice::whereIn('company_id', $companyIds)->where('status', 'draft')->count(),
                    'issued' => Invoice::whereIn('company_id', $companyIds)->where('status', 'issued')->count(),
                    'paid' => Invoice::whereIn('company_id', $companyIds)->where('status', 'paid')->count(),
                    'overdue' => Invoice::whereIn('company_id', $companyIds)->where('status', 'overdue')->count(),
                    'cancelled' => Invoice::whereIn('company_id', $companyIds)->where('status', 'cancelled')->count(),
                ],
                'total_revenue' => Invoice::whereIn('company_id', $companyIds)->where('status', 'paid')->sum('total_amount'),
                'pending_revenue' => Invoice::whereIn('company_id', $companyIds)->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
                'this_month_revenue' => Invoice::whereIn('company_id', $companyIds)
                    ->where('status', 'paid')
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('total_amount'),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check for overdue invoices
     */
    public function checkOverdue(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $request->input('company_id');

            if ($companyId) {
                $company = Company::findOrFail($companyId);
                if ($company->user_id !== $user->id) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                $updated = $this->invoiceService->checkOverdueInvoices($companyId);
            } else {
                $updated = 0;
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
                foreach ($companyIds as $cid) {
                    $updated += $this->invoiceService->checkOverdueInvoices($cid);
                }
            }

            return response()->json([
                'message' => $updated > 0 ? "{$updated} invoices marked as overdue" : 'No overdue invoices',
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to check overdue invoices', 'message' => $e->getMessage()], 500);
        }
    }
}
