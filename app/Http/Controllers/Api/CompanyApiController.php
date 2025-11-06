<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all companies for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $companies = Company::where('user_id', $request->user()->id)
            ->with(['contracts', 'invoices'])
            ->get();

        return response()->json($companies);
    }

    /**
     * Store a new company
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cui' => 'nullable|string|max:50',
            'reg_com' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'bank_account' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
        ]);

        $company = Company::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'is_active' => true,
        ]));

        return response()->json($company, 201);
    }

    /**
     * Show a specific company
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)
            ->with(['contracts', 'invoices', 'integrations'])
            ->findOrFail($id);

        return response()->json($company);
    }

    /**
     * Update a company
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cui' => 'nullable|string|max:50',
            'reg_com' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'bank_account' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    /**
     * Delete a company
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)->findOrFail($id);

        // Check if company has contracts or invoices
        if ($company->contracts()->count() > 0 || $company->invoices()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete company with existing contracts or invoices'
            ], 422);
        }

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}
