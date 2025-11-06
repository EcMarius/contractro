<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LicenseResource;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {
    }

    /**
     * Validate a license key and domain
     *
     * POST /api/licenses/validate
     * Body: { "license_key": "XXXX-...", "domain": "example.com" }
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string|size:29', // XXXX-XXXX-XXXX-XXXX-XXXX format
            'domain' => 'required|string|max:255',
            'product_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->licenseService->validateLicense(
            licenseKey: $request->input('license_key'),
            domain: $request->input('domain'),
            ipAddress: $request->ip(),
            checkType: 'api'
        );

        $statusCode = $result['valid'] ? 200 : 403;

        return response()->json($result, $statusCode);
    }

    /**
     * Check if a domain has any active license (public endpoint)
     *
     * GET /api/licenses/check?domain=example.com
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Domain parameter is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->licenseService->checkDomainLicense(
            domain: $request->input('domain'),
            ipAddress: $request->ip()
        );

        return response()->json($result);
    }

    /**
     * Get license details by key (requires authentication)
     *
     * GET /api/licenses/{licenseKey}
     */
    public function show(string $licenseKey): JsonResponse
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return response()->json([
                'error' => 'License not found',
                'code' => 'LICENSE_NOT_FOUND',
            ], 404);
        }

        // Check if user has permission to view this license
        if (auth()->user()->id !== $license->user_id && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Unauthorized',
                'code' => 'UNAUTHORIZED',
            ], 403);
        }

        return response()->json([
            'license' => new LicenseResource($license),
        ]);
    }

    /**
     * Get all licenses for authenticated user
     *
     * GET /api/licenses
     */
    public function index(Request $request): JsonResponse
    {
        $query = License::query();

        // If not admin, only show user's own licenses
        if (!auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('domain')) {
            $query->where('domain', 'like', '%' . $request->input('domain') . '%');
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $licenses = $query->paginate($perPage);

        return response()->json([
            'data' => LicenseResource::collection($licenses),
            'meta' => [
                'total' => $licenses->total(),
                'per_page' => $licenses->perPage(),
                'current_page' => $licenses->currentPage(),
                'last_page' => $licenses->lastPage(),
            ],
        ]);
    }

    /**
     * Get license check logs
     *
     * GET /api/licenses/{licenseKey}/logs
     */
    public function logs(string $licenseKey, Request $request): JsonResponse
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return response()->json([
                'error' => 'License not found',
            ], 404);
        }

        // Check permission
        if (auth()->user()->id !== $license->user_id && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $logs = $license->checkLogs()
            ->orderBy('checked_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get license statistics (admin only)
     *
     * GET /api/licenses/statistics
     */
    public function statistics(): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->licenseService->getLicenseStats();

        return response()->json([
            'statistics' => $stats,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Renew a license (requires authentication)
     *
     * POST /api/licenses/{licenseKey}/renew
     */
    public function renew(string $licenseKey): JsonResponse
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return response()->json([
                'error' => 'License not found',
            ], 404);
        }

        // Check permission
        if (auth()->user()->id !== $license->user_id && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $license->renew();

        return response()->json([
            'message' => 'License renewed successfully',
            'license' => new LicenseResource($license->fresh()),
        ]);
    }
}
