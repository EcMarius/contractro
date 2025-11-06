<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all integrations for user's companies
     */
    public function index(Request $request): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');

        $integrations = Integration::whereIn('company_id', $companyIds)
            ->with(['company', 'logs' => function($q) {
                $q->latest()->limit(10);
            }])
            ->get();

        // Mask sensitive config data
        $integrations->transform(function($integration) {
            $integration->config = $integration->getSafeConfig();
            return $integration;
        });

        return response()->json($integrations);
    }

    /**
     * Store a new integration
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'type' => 'required|string',
            'provider' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'config' => 'required|array',
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
        ]);

        // Verify company belongs to user
        $company = Company::where('id', $validated['company_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $integration = Integration::create($validated);

        return response()->json($integration, 201);
    }

    /**
     * Show a specific integration
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');

        $integration = Integration::whereIn('company_id', $companyIds)
            ->with(['company', 'logs' => function($q) {
                $q->latest()->limit(50);
            }])
            ->findOrFail($id);

        $integration->config = $integration->getSafeConfig();

        return response()->json($integration);
    }

    /**
     * Update an integration
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');
        $integration = Integration::whereIn('company_id', $companyIds)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'config' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'is_test_mode' => 'sometimes|boolean',
        ]);

        $integration->update($validated);

        return response()->json($integration);
    }

    /**
     * Delete an integration
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');
        $integration = Integration::whereIn('company_id', $companyIds)->findOrFail($id);

        $integration->delete();

        return response()->json(['message' => 'Integration deleted successfully']);
    }

    /**
     * Test an integration
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');
        $integration = Integration::whereIn('company_id', $companyIds)->findOrFail($id);

        // Test based on integration type
        $result = match($integration->type) {
            Integration::TYPE_ANAF => $this->testAnafIntegration($integration),
            Integration::TYPE_SMS => $this->testSmsIntegration($integration),
            default => ['success' => false, 'message' => 'Integration test not implemented for this type'],
        };

        // Log the test
        $integration->logs()->create([
            'action' => 'test',
            'status' => $result['success'] ? 'success' : 'failed',
            'response_data' => $result,
        ]);

        return response()->json($result);
    }

    /**
     * Get integration logs
     */
    public function logs(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');
        $integration = Integration::whereIn('company_id', $companyIds)->findOrFail($id);

        $logs = $integration->logs()
            ->latest()
            ->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Toggle integration active status
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $companyIds = Company::where('user_id', $request->user()->id)->pluck('id');
        $integration = Integration::whereIn('company_id', $companyIds)->findOrFail($id);

        $integration->update(['is_active' => !$integration->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $integration->is_active,
        ]);
    }

    protected function testAnafIntegration(Integration $integration): array
    {
        try {
            $anafService = app(\App\Services\AnafService::class);

            // Test CUI validation with a known valid CUI (ANAF itself)
            $result = $anafService->validateCUI('4281021');

            if ($result['valid']) {
                return [
                    'success' => true,
                    'message' => 'ANAF integration test successful',
                    'data' => $result,
                ];
            }

            return [
                'success' => false,
                'message' => 'ANAF integration test failed',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'ANAF integration error: ' . $e->getMessage(),
            ];
        }
    }

    protected function testSmsIntegration(Integration $integration): array
    {
        try {
            // For test, we just validate the config
            if (!$integration->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'SMS integration is not properly configured',
                ];
            }

            return [
                'success' => true,
                'message' => 'SMS integration configuration is valid',
                'note' => 'Actual SMS sending requires phone number and message',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMS integration error: ' . $e->getMessage(),
            ];
        }
    }
}
