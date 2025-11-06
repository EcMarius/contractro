<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return auth()->user();
});

// Update user country (for onboarding) - uses web middleware to work with session auth
Route::middleware('web', 'auth')->post('/user/update-country', function (Request $request) {
    $request->validate([
        'country' => 'required|string|max:2'
    ]);

    $user = auth()->user();
    $user->country = $request->country;
    $user->save();

    return response()->json(['success' => true, 'message' => 'Country updated successfully']);
});

// Company lookup API - Auto-fill company data from registration code
Route::middleware('web', 'auth')->post('/company/lookup', function (Request $request) {
    $request->validate([
        'registration_code' => 'required|string',
        'country' => 'required|string|max:2',
    ]);

    $lookupService = app(\App\Services\CompanyLookupService::class);
    $result = $lookupService->lookup(
        $request->registration_code,
        $request->country
    );

    return response()->json($result);
});

Wave::api();

// Posts Example API Route
Route::middleware('auth:api')->group(function () {
    Route::get('/posts', [\App\Http\Controllers\Api\ApiController::class, 'posts']);
});

/*
|--------------------------------------------------------------------------
| ContractRO API Routes
|--------------------------------------------------------------------------
|
| These routes are protected by the api.key middleware which validates
| API keys from the X-API-Key header or api_key parameter.
|
*/

// Public auth endpoint (no middleware required)
Route::get('/v1/auth/validate', [\App\Http\Controllers\Api\AuthController::class, 'validate']);

/*
|--------------------------------------------------------------------------
| Browser Extension API Routes
|--------------------------------------------------------------------------
|
| These routes are protected by Laravel Sanctum for the browser extension.
|
*/

// Extension auth endpoints (public)
Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Public settings endpoint (for extension logo, etc.)
Route::get('/settings', function () {
    $logoPath = setting('site.logo');
    $logoUrl = null;

    if ($logoPath) {
        // Check if it starts with http:// or https://
        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            $logoUrl = $logoPath;
        } else {
            // Try to get public asset path
            $logoUrl = url('storage/' . str_replace('public/', '', $logoPath));
        }
    }

    return response()->json([
        'logo' => $logoUrl,
        'name' => setting('site.name', 'ContractRO'),
    ])->header('Access-Control-Allow-Origin', '*')
      ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
      ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

// OPTIONS endpoints for CORS preflight
Route::options('/settings', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

Route::options('/stats', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/campaigns', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/leads', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/extension/campaigns/{id}/context', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

// Extension protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::get('/auth/subscription', [\App\Http\Controllers\Api\AuthController::class, 'subscription']);
    Route::get('/auth/validate-plan', [\App\Http\Controllers\Api\AuthController::class, 'validatePlan']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    // Stats (with explicit CORS headers for browser extensions)
    Route::get('/stats', function () {
        $controller = app(\App\Http\Controllers\Api\AuthController::class);
        $response = $controller->stats(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    // Campaigns (with CORS)
    Route::get('/campaigns', function () {
        $controller = app(\App\Http\Controllers\Api\CampaignController::class);
        $response = $controller->index(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    Route::get('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);

    // Leads (from extension) with CORS
    Route::get('/leads', function () {
        $controller = app(\App\Http\Controllers\Api\LeadController::class);
        $response = $controller->index(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    Route::post('/campaigns/{campaignId}/leads', [\App\Http\Controllers\Api\LeadController::class, 'store']);
    Route::post('/campaigns/{campaignId}/leads/bulk', [\App\Http\Controllers\Api\LeadController::class, 'storeBulk']);
    Route::patch('/leads/{id}/status', [\App\Http\Controllers\Api\LeadController::class, 'updateStatus']);
    Route::delete('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'destroy']);

    // Extension helper endpoints
    Route::post('/extension/generate-search-terms', [\App\Http\Controllers\Api\ExtensionController::class, 'generateSearchTerms']);
    Route::post('/extension/validate-lead', [\App\Http\Controllers\Api\ExtensionController::class, 'validateLead']);
    Route::post('/extension/record-sync-start', [\App\Http\Controllers\Api\ExtensionController::class, 'recordSyncStart']);
    Route::get('/extension/validate-token', [\App\Http\Controllers\Api\ExtensionController::class, 'validateToken']);

    // Campaign context endpoint
    Route::get('/extension/campaigns/{id}/context', function ($id) {
        $controller = app(\App\Http\Controllers\Api\ExtensionController::class);
        $response = $controller->getCampaignContext($id);
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    // Schema management endpoints (for admin)
    Route::prefix('admin/schemas')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SchemaController::class, 'index']);
        Route::get('/missing', [\App\Http\Controllers\Api\SchemaController::class, 'missing']);
        Route::get('/{platform}/{pageType}', [\App\Http\Controllers\Api\SchemaController::class, 'show']);
        Route::get('/{platform}/{pageType}/export', [\App\Http\Controllers\Api\SchemaController::class, 'export']);
        Route::get('/{platform}/{pageType}/history', [\App\Http\Controllers\Api\SchemaController::class, 'history']);
        Route::post('/import', [\App\Http\Controllers\Api\SchemaController::class, 'import']);
        Route::post('/bulk-import', [\App\Http\Controllers\Api\SchemaController::class, 'bulkImport']);
        Route::post('/test-selector', [\App\Http\Controllers\Api\SchemaController::class, 'testSelector']);
        Route::post('/', [\App\Http\Controllers\Api\SchemaController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\SchemaController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\SchemaController::class, 'destroy']);
        Route::post('/clear-cache', [\App\Http\Controllers\Api\SchemaController::class, 'clearCache']);
        Route::post('/{platform}/{pageType}/rollback', [\App\Http\Controllers\Api\SchemaController::class, 'rollback']);
    });

    // Schema endpoints for extension (read-only)
    Route::get('/extension/schemas/{platform}/{pageType}', [\App\Http\Controllers\Api\SchemaController::class, 'show']);
});

// Protected API routes with plan limit enforcement
Route::middleware('api.key')->prefix('v1')->group(function () {

    // Campaign endpoints
    Route::get('/campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);
    Route::post('/campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'store'])
        ->middleware('enforce.limits:campaigns');  // CHECK CAMPAIGN LIMIT
    Route::put('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'update']);
    Route::delete('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'destroy']);

    // Lead endpoints
    Route::get('/leads', [\App\Http\Controllers\Api\LeadController::class, 'index']);
    Route::get('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'show']);
    Route::delete('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'destroy']);
    Route::post('/leads/bulk-delete', [\App\Http\Controllers\Api\LeadController::class, 'bulkDestroy']);
    Route::patch('/leads/{id}/status', [\App\Http\Controllers\Api\LeadController::class, 'updateStatus']);

    // Sync endpoints
    Route::post('/sync/campaign/{id}', [\App\Http\Controllers\Api\SyncController::class, 'syncCampaign'])
        ->middleware('enforce.limits:manual_sync');  // CHECK MANUAL SYNC LIMIT
    Route::post('/sync/all', [\App\Http\Controllers\Api\SyncController::class, 'syncAll'])
        ->middleware('enforce.limits:manual_sync');  // CHECK MANUAL SYNC LIMIT
    Route::get('/sync/history/{id}', [\App\Http\Controllers\Api\SyncController::class, 'syncHistory']);
    Route::get('/sync/campaign/{campaignId}/running', [\App\Http\Controllers\Api\SyncController::class, 'getRunningSyncForCampaign']);
    Route::get('/sync/{syncId}', [\App\Http\Controllers\Api\SyncController::class, 'getSyncDetails']);

    // Account endpoints
    Route::get('/account/usage', [\App\Http\Controllers\Api\AccountController::class, 'usage']);
});

/*
|--------------------------------------------------------------------------
| ContractRO API Routes (Contracts, Invoices, Companies, Integrations)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1/contractro')->group(function () {

    // Companies API
    Route::apiResource('companies', \App\Http\Controllers\Api\CompanyApiController::class);
    Route::post('/companies/{id}/validate-cui', function($id) {
        $company = \App\Models\Company::findOrFail($id);
        $anafService = app(\App\Services\AnafService::class);
        $result = $anafService->validateCUI($company->cui);
        return response()->json($result);
    });

    // Contracts API
    Route::apiResource('contracts', \App\Http\Controllers\Api\ContractApiController::class);
    Route::post('/contracts/{id}/send-for-signing', [\App\Http\Controllers\Api\ContractApiController::class, 'sendForSigning']);
    Route::post('/contracts/{id}/duplicate', [\App\Http\Controllers\Api\ContractApiController::class, 'duplicate']);
    Route::post('/contracts/{id}/terminate', [\App\Http\Controllers\Api\ContractApiController::class, 'terminate']);
    Route::get('/contracts/{id}/signing-status', [\App\Http\Controllers\Api\ContractApiController::class, 'signingStatus']);
    Route::get('/contracts/{id}/eidas-report', [\App\Http\Controllers\Api\ContractApiController::class, 'eidasReport']);
    
    // Contract Parties
    Route::post('/contracts/{id}/parties', [\App\Http\Controllers\Api\ContractApiController::class, 'addParty']);
    Route::delete('/contracts/{contractId}/parties/{partyId}', [\App\Http\Controllers\Api\ContractApiController::class, 'removeParty']);
    
    // Contract Amendments
    Route::post('/contracts/{id}/amendments', [\App\Http\Controllers\Api\ContractApiController::class, 'addAmendment']);
    Route::get('/contracts/{id}/amendments', [\App\Http\Controllers\Api\ContractApiController::class, 'amendments']);
    
    // Contract Attachments
    Route::post('/contracts/{id}/attachments', [\App\Http\Controllers\Api\ContractApiController::class, 'addAttachment']);
    Route::get('/contracts/{id}/attachments', [\App\Http\Controllers\Api\ContractApiController::class, 'attachments']);

    // Invoices API
    Route::apiResource('invoices', \App\Http\Controllers\Api\InvoiceApiController::class);
    Route::post('/invoices/{id}/mark-paid', [\App\Http\Controllers\Api\InvoiceApiController::class, 'markAsPaid']);
    Route::post('/invoices/{id}/cancel', [\App\Http\Controllers\Api\InvoiceApiController::class, 'cancel']);
    Route::post('/invoices/{id}/send-to-anaf', [\App\Http\Controllers\Api\InvoiceApiController::class, 'sendToAnaf']);
    Route::get('/invoices/{id}/anaf-status', [\App\Http\Controllers\Api\InvoiceApiController::class, 'anafStatus']);
    Route::post('/invoices/from-contract/{contractId}', [\App\Http\Controllers\Api\InvoiceApiController::class, 'createFromContract']);

    // Integrations API
    Route::apiResource('integrations', \App\Http\Controllers\Api\IntegrationApiController::class);
    Route::post('/integrations/{id}/test', [\App\Http\Controllers\Api\IntegrationApiController::class, 'test']);
    Route::get('/integrations/{id}/logs', [\App\Http\Controllers\Api\IntegrationApiController::class, 'logs']);
    Route::post('/integrations/{id}/toggle', [\App\Http\Controllers\Api\IntegrationApiController::class, 'toggle']);

    // ANAF Utilities
    Route::post('/anaf/validate-cui', function(\Illuminate\Http\Request $request) {
        $request->validate(['cui' => 'required|string']);
        $anafService = app(\App\Services\AnafService::class);
        $result = $anafService->validateCUI($request->cui);
        return response()->json($result);
    });

    // SMS Utilities
    Route::post('/sms/send', function(\Illuminate\Http\Request $request) {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:160'
        ]);
        $smsService = app(\App\Services\SmsService::class);
        $sent = $smsService->send($request->phone, $request->message);
        return response()->json(['success' => $sent]);
    });

    // Contract Types
    Route::get('/contract-types', function() {
        return response()->json(\App\Models\ContractType::where('is_active', true)->get());
    });

    // Statistics
    Route::get('/stats/dashboard', function(\Illuminate\Http\Request $request) {
        $user = $request->user();
        $companyIds = \App\Models\Company::where('user_id', $user->id)->pluck('id');
        
        return response()->json([
            'companies' => \App\Models\Company::where('user_id', $user->id)->count(),
            'contracts' => [
                'total' => \App\Models\Contract::whereIn('company_id', $companyIds)->count(),
                'active' => \App\Models\Contract::whereIn('company_id', $companyIds)->where('status', 'active')->count(),
                'pending' => \App\Models\Contract::whereIn('company_id', $companyIds)->where('status', 'pending')->count(),
                'signed_this_month' => \App\Models\Contract::whereIn('company_id', $companyIds)
                    ->where('status', 'signed')
                    ->whereMonth('signed_at', now()->month)
                    ->count(),
            ],
            'invoices' => [
                'total' => \App\Models\Invoice::whereIn('company_id', $companyIds)->count(),
                'paid' => \App\Models\Invoice::whereIn('company_id', $companyIds)->where('status', 'paid')->count(),
                'pending_amount' => \App\Models\Invoice::whereIn('company_id', $companyIds)
                    ->whereIn('status', ['issued', 'overdue'])
                    ->sum('total_amount'),
                'overdue' => \App\Models\Invoice::whereIn('company_id', $companyIds)
                    ->where('status', 'overdue')
                    ->count(),
            ],
        ]);
    });
});
