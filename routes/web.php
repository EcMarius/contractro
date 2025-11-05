<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Wave\Facades\Wave;
use App\Http\Controllers\PluginUploadController;
use App\Http\Controllers\BlogAIController;

// NOTE: Email verification route is registered in AppServiceProvider::boot()
// to ensure it overrides DevDojo Auth's route (which uses signed URLs)

// Language switching route
Route::get('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

// Plugin upload route (AJAX)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/admin/plugins/upload', [PluginUploadController::class, 'upload'])->name('admin.plugins.upload');

    // API Keys list for documentation (authenticated users only)
    Route::get('/api/user/api-keys', [\App\Http\Controllers\Api\UserApiKeysController::class, 'index'])->name('user.api-keys');

    // Lead detail view
    Route::get('/dashboard/leads/{id}', function($id) {
        return view('theme::pages.dashboard.leads.view', ['id' => $id]);
    })->name('dashboard.leads.view');

    // Blog AI routes
    Route::prefix('admin/blog/ai')->name('admin.blog.ai.')->group(function () {
        Route::get('/models', [BlogAIController::class, 'getModels'])->name('models');
        Route::post('/generate', [BlogAIController::class, 'generateContent'])->name('generate');
        Route::post('/edit', [BlogAIController::class, 'editText'])->name('edit');
        Route::post('/shorter', [BlogAIController::class, 'makeShorter'])->name('shorter');
        Route::post('/longer', [BlogAIController::class, 'makeLonger'])->name('longer');
        Route::post('/seo', [BlogAIController::class, 'optimizeForSEO'])->name('seo');
        Route::post('/reword', [BlogAIController::class, 'reword'])->name('reword');
    });
});

// Override default DevDojo Auth social routes with custom controller
// DISABLED: Using SocialAuth plugin routes instead (/social/auth/{provider})
// Route::get('auth/{driver}/redirect', [\App\Http\Controllers\Auth\CustomSocialController::class, 'redirect'])->name('auth.redirect');
// Route::get('auth/{driver}/callback', [\App\Http\Controllers\Auth\CustomSocialController::class, 'callback'])->name('auth.callback');

// General OAuth 2.0 Routes (for Browser Extension, Mobile Apps, etc.)
Route::get('/oauth/authorize', [\App\Http\Controllers\OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::get('/oauth/consent', [\App\Http\Controllers\OAuthController::class, 'showConsent'])->name('oauth.consent')->middleware('auth');
Route::post('/oauth/consent', [\App\Http\Controllers\OAuthController::class, 'handleConsent'])->name('oauth.consent.handle')->middleware('auth');
Route::get('/oauth/callback', [\App\Http\Controllers\OAuthController::class, 'callback'])->name('oauth.callback');

// Organization routes (for seated plans)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/organization/setup', [\App\Http\Controllers\OrganizationController::class, 'setup'])->name('organization.setup');
    Route::post('/organization/store', [\App\Http\Controllers\OrganizationController::class, 'store'])->name('organization.store');
    Route::get('/team', [\App\Http\Controllers\OrganizationController::class, 'team'])->name('organization.team');
    Route::get('/team/add', [\App\Http\Controllers\OrganizationController::class, 'addMemberForm'])->name('organization.add-member');
    Route::post('/team/add', [\App\Http\Controllers\OrganizationController::class, 'storeMember'])->name('organization.store-member');
    Route::get('/team/{member}', [\App\Http\Controllers\OrganizationController::class, 'showMember'])->name('organization.show-member');
    Route::delete('/team/{member}', [\App\Http\Controllers\OrganizationController::class, 'destroyMember'])->name('organization.destroy-member');
});

// Plan-related routes (must be outside PlanCheck middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/plan-selection', function () {
        return view('theme::pages.plan-selection');
    })->name('plan-selection');

    Route::get('/trial-ended', function () {
        return view('theme::pages.trial-ended');
    })->name('trial-ended');

    Route::get('/plan-expired', function () {
        return view('theme::pages.plan-expired');
    })->name('plan-expired');

    // Quick checkout route (redirects directly to Stripe)
    Route::get('/checkout/{plan}', function (\Wave\Plan $plan) {
        \Log::info('CHECKOUT ROUTE HIT', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing' => request()->get('billing'),
            'user_id' => auth()->user()?->id,
        ]);

        $billingCycle = request()->get('billing', 'monthly');
        $billingCycleNormalized = in_array($billingCycle, ['monthly', 'month']) ? 'month' : 'year';

        // Get seat quantity for seated plans (default to 1)
        $seats = request()->get('seats', 1);
        $seats = max(1, min(50, (int) $seats)); // Ensure seats is between 1 and 50

        // Use StripeService to get correct credentials (respects test/live mode from ContractRO settings)
        $stripeService = app(\App\Services\StripeService::class);

        \Log::info('Stripe configured check', ['is_configured' => $stripeService->isConfigured()]);

        if (!$stripeService->isConfigured()) {
            \Log::error('CHECKOUT FAILED: Stripe not configured');
            return redirect()->route('plan-selection')->with('error', 'Stripe is not configured. Please contact support.');
        }

        $stripe = new \Stripe\StripeClient($stripeService->getSecretKey());
        $priceId = $billingCycleNormalized == 'month' ? $plan->monthly_price_id : $plan->yearly_price_id;

        \Log::info('Price ID check', [
            'billing_cycle' => $billingCycleNormalized,
            'monthly_price_id' => $plan->monthly_price_id,
            'yearly_price_id' => $plan->yearly_price_id,
            'selected_price_id' => $priceId,
        ]);

        if (!$priceId) {
            \Log::error('CHECKOUT FAILED: No price ID for plan', [
                'plan' => $plan->name,
                'billing' => $billingCycleNormalized,
            ]);
            return redirect()->route('plan-selection')->with('error', 'Invalid plan or billing cycle.');
        }

        $sessionData = [
            'line_items' => [[
                'price' => $priceId,
                'quantity' => $plan->is_seated_plan ? $seats : 1,
            ]],
            'metadata' => [
                'billable_type' => 'user',
                'billable_id' => auth()->user()->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycleNormalized,
                'seats' => $plan->is_seated_plan ? $seats : 1,
            ],
            'mode' => 'subscription',
            'success_url' => url('subscription/welcome'),
            'cancel_url' => url('plan-selection'),
        ];

        // Add trial period if user doesn't have a subscription and hasn't used trial
        $user = auth()->user();
        $trialDays = (int) \Wave\Plugins\ContractRO\Models\Setting::getValue('trial_days', 7);
        $defaultTrialPlanId = \Wave\Plugins\ContractRO\Models\Setting::getValue('trial_plan_id', null);

        \Log::info('Trial check', [
            'trial_days' => $trialDays,
            'default_trial_plan_id' => $defaultTrialPlanId,
            'current_plan_id' => $plan->id,
            'user_has_subscription' => !is_null($user->subscription),
            'user_trial_ends_at' => $user->trial_ends_at,
        ]);

        if ($trialDays > 0 && $defaultTrialPlanId == $plan->id && !$user->subscription && empty($user->trial_ends_at)) {
            \Log::info('APPLYING TRIAL', ['days' => $trialDays]);
            $sessionData['subscription_data'] = [
                'trial_period_days' => $trialDays,
            ];
        } else {
            \Log::warning('Trial NOT applied', [
                'reason' => $trialDays <= 0 ? 'trial_days_zero' : ($defaultTrialPlanId != $plan->id ? 'not_trial_plan' : ($user->subscription ? 'has_subscription' : 'already_used_trial'))
            ]);
        }

        $checkoutSession = $stripe->checkout->sessions->create($sessionData);

        return redirect()->to($checkoutSession->url);
    })->name('checkout.quick');
});

// Test route for AI debug button
Route::get('/error/test', function () {
    // Trigger an intentional error for testing
    throw new \Exception('Test error for AI debug button - This is a demo error!');
})->middleware('web');

// Data deletion request route (for Facebook App compliance)
Route::get('/settings/data-deletion', App\Livewire\Settings\DataDeletionRequest::class)
    ->name('settings.data-deletion');

// Stripe webhook alias route (for backward compatibility with ngrok setup)
Route::post('stripe/webhook', '\Wave\Http\Controllers\Billing\Webhooks\StripeWebhook@handler');

// Webhook test endpoint - verify webhooks can reach server
Route::match(['get', 'post'], 'stripe/webhook/test', function(\Illuminate\Http\Request $request) {
    \Log::info('Webhook test endpoint hit', [
        'method' => $request->method(),
        'ip' => $request->ip(),
        'headers' => $request->headers->all(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Webhook endpoint is accessible',
        'timestamp' => now()->toDateTimeString(),
        'ip' => $request->ip(),
    ]);
});

// Stripe Customer Portal redirect
Route::get('stripe/portal', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Get user's subscription
    $subscription = \Wave\Subscription::where('billable_id', $user->id)
        ->where('billable_type', 'user')
        ->where('status', 'active')
        ->first();

    if (!$subscription || !$subscription->vendor_customer_id) {
        return redirect()->route('settings.subscription')->with('error', 'No active subscription found.');
    }

    try {
        // Use StripeService to get correct credentials (respects test/live mode)
        $stripeService = app(\App\Services\StripeService::class);

        if (!$stripeService->isConfigured()) {
            return redirect()->route('settings.subscription')->with('error', 'Stripe is not configured.');
        }

        $stripe = new \Stripe\StripeClient($stripeService->getSecretKey());

        // Create a billing portal session
        $session = $stripe->billingPortal->sessions->create([
            'customer' => $subscription->vendor_customer_id,
            'return_url' => url('/settings/subscription'),
        ]);

        return redirect($session->url);
    } catch (\Exception $e) {
        \Log::error('Stripe portal error: ' . $e->getMessage());
        return redirect()->route('settings.subscription')->with('error', 'Unable to access billing portal.');
    }
})->middleware('auth')->name('stripe.portal');

// Growth Hacking routes (public)
Route::get('/welcome/setup-password/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'welcome'])->name('growth-hack.welcome');
Route::post('/welcome/set-password', [\App\Http\Controllers\GrowthHackingController::class, 'setPassword'])->name('growth-hack.set-password');
Route::get('/unsubscribe/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'unsubscribe'])->name('growth-hack.unsubscribe');
Route::post('/unsubscribe/process', [\App\Http\Controllers\GrowthHackingController::class, 'processUnsubscribe'])->name('growth-hack.unsubscribe.process');
Route::get('/track/open/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'trackOpen'])->name('growth-hack.track-open');
Route::get('/track/click', [\App\Http\Controllers\GrowthHackingController::class, 'trackClick'])->name('growth-hack.track-click');

// ContractRO - Public Signing Routes (no authentication required)
Route::prefix('sign')->name('signing.')->group(function () {
    Route::get('/{partyId}/{token}', [\App\Http\Controllers\ContractSigningController::class, 'show'])->name('show');
    Route::post('/{partyId}/{token}/initiate-sms', [\App\Http\Controllers\ContractSigningController::class, 'initiateSms'])->name('initiate-sms');
    Route::post('/{partyId}/{token}/verify-sms', [\App\Http\Controllers\ContractSigningController::class, 'verifySms'])->name('verify-sms');
    Route::post('/{partyId}/{token}/resend-sms', [\App\Http\Controllers\ContractSigningController::class, 'resendSms'])->name('resend-sms');
    Route::post('/{partyId}/{token}/upload-handwritten', [\App\Http\Controllers\ContractSigningController::class, 'uploadHandwritten'])->name('upload-handwritten');
    Route::get('/{partyId}/{token}/success', [\App\Http\Controllers\ContractSigningController::class, 'success'])->name('success');
    Route::get('/{partyId}/{token}/download', [\App\Http\Controllers\ContractSigningController::class, 'downloadPdf'])->name('download');
});

// ContractRO - Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // Company Routes
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CompanyController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\CompanyController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [\App\Http\Controllers\CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [\App\Http\Controllers\CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [\App\Http\Controllers\CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [\App\Http\Controllers\CompanyController::class, 'destroy'])->name('destroy');
        Route::post('/{company}/switch', [\App\Http\Controllers\CompanyController::class, 'switch'])->name('switch');
        Route::post('/validate-cui', [\App\Http\Controllers\CompanyController::class, 'validateCui'])->name('validate-cui');
    });

    // Contract Routes
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ContractController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ContractController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ContractController::class, 'store'])->name('store');
        Route::get('/{contract}', [\App\Http\Controllers\ContractController::class, 'show'])->name('show');
        Route::get('/{contract}/edit', [\App\Http\Controllers\ContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [\App\Http\Controllers\ContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [\App\Http\Controllers\ContractController::class, 'destroy'])->name('destroy');
        Route::post('/{contract}/send-for-signing', [\App\Http\Controllers\ContractController::class, 'sendForSigning'])->name('send-for-signing');
        Route::post('/{contract}/cancel', [\App\Http\Controllers\ContractController::class, 'cancel'])->name('cancel');
        Route::post('/{contract}/duplicate', [\App\Http\Controllers\ContractController::class, 'duplicate'])->name('duplicate');
        Route::get('/{contract}/download', [\App\Http\Controllers\ContractController::class, 'downloadPdf'])->name('download');
        Route::post('/{contract}/add-party', [\App\Http\Controllers\ContractController::class, 'addParty'])->name('add-party');
        Route::delete('/{contract}/parties/{partyId}', [\App\Http\Controllers\ContractController::class, 'removeParty'])->name('remove-party');

        // Contract Amendments
        Route::prefix('{contract}/amendments')->name('amendments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ContractAmendmentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ContractAmendmentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\ContractAmendmentController::class, 'store'])->name('store');
            Route::get('/{amendment}', [\App\Http\Controllers\ContractAmendmentController::class, 'show'])->name('show');
            Route::get('/{amendment}/edit', [\App\Http\Controllers\ContractAmendmentController::class, 'edit'])->name('edit');
            Route::put('/{amendment}', [\App\Http\Controllers\ContractAmendmentController::class, 'update'])->name('update');
            Route::delete('/{amendment}', [\App\Http\Controllers\ContractAmendmentController::class, 'destroy'])->name('destroy');
            Route::get('/{amendment}/download', [\App\Http\Controllers\ContractAmendmentController::class, 'download'])->name('download');
        });

        // Contract Attachments
        Route::prefix('{contract}/attachments')->name('attachments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ContractAttachmentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ContractAttachmentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\ContractAttachmentController::class, 'store'])->name('store');
            Route::post('/bulk', [\App\Http\Controllers\ContractAttachmentController::class, 'bulkUpload'])->name('bulk-upload');
            Route::get('/{attachment}', [\App\Http\Controllers\ContractAttachmentController::class, 'show'])->name('show');
            Route::get('/{attachment}/edit', [\App\Http\Controllers\ContractAttachmentController::class, 'edit'])->name('edit');
            Route::put('/{attachment}', [\App\Http\Controllers\ContractAttachmentController::class, 'update'])->name('update');
            Route::delete('/{attachment}', [\App\Http\Controllers\ContractAttachmentController::class, 'destroy'])->name('destroy');
            Route::get('/{attachment}/download', [\App\Http\Controllers\ContractAttachmentController::class, 'download'])->name('download');
        });

        // Contract Tasks
        Route::prefix('{contract}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ContractTaskController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ContractTaskController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\ContractTaskController::class, 'store'])->name('store');
            Route::get('/{task}', [\App\Http\Controllers\ContractTaskController::class, 'show'])->name('show');
            Route::get('/{task}/edit', [\App\Http\Controllers\ContractTaskController::class, 'edit'])->name('edit');
            Route::put('/{task}', [\App\Http\Controllers\ContractTaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [\App\Http\Controllers\ContractTaskController::class, 'destroy'])->name('destroy');
            Route::post('/{task}/complete', [\App\Http\Controllers\ContractTaskController::class, 'complete'])->name('complete');
            Route::post('/{task}/start', [\App\Http\Controllers\ContractTaskController::class, 'start'])->name('start');
            Route::post('/{task}/reopen', [\App\Http\Controllers\ContractTaskController::class, 'reopen'])->name('reopen');
            Route::post('/{task}/assign', [\App\Http\Controllers\ContractTaskController::class, 'assign'])->name('assign');
        });
    });

    // Invoice Routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\InvoiceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'destroy'])->name('destroy');
        Route::post('/{invoice}/issue', [\App\Http\Controllers\InvoiceController::class, 'issue'])->name('issue');
        Route::post('/{invoice}/mark-as-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('mark-as-paid');
        Route::post('/{invoice}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel'])->name('cancel');
        Route::post('/{invoice}/duplicate', [\App\Http\Controllers\InvoiceController::class, 'duplicate'])->name('duplicate');
        Route::get('/{invoice}/download', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('download');
        Route::post('/{invoice}/send-email', [\App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('send-email');
        Route::get('/from-contract/{contract}', [\App\Http\Controllers\InvoiceController::class, 'createFromContract'])->name('from-contract');
    });

    // Financial Report Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\FinancialReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/create', [\App\Http\Controllers\FinancialReportController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FinancialReportController::class, 'store'])->name('store');
        Route::get('/{report}', [\App\Http\Controllers\FinancialReportController::class, 'show'])->name('show');
        Route::delete('/{report}', [\App\Http\Controllers\FinancialReportController::class, 'destroy'])->name('destroy');
        Route::post('/{report}/refresh', [\App\Http\Controllers\FinancialReportController::class, 'refresh'])->name('refresh');
        Route::get('/{report}/download', [\App\Http\Controllers\FinancialReportController::class, 'downloadPdf'])->name('download');
        Route::get('/{report}/export', [\App\Http\Controllers\FinancialReportController::class, 'exportExcel'])->name('export');
        Route::post('/revenue', [\App\Http\Controllers\FinancialReportController::class, 'revenueReport'])->name('revenue');
        Route::post('/cleanup', [\App\Http\Controllers\FinancialReportController::class, 'cleanup'])->name('cleanup');
    });

    // My Tasks Routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/my-tasks', [\App\Http\Controllers\ContractTaskController::class, 'myTasks'])->name('my-tasks');
        Route::get('/overdue', [\App\Http\Controllers\ContractTaskController::class, 'overdue'])->name('overdue');
    });
});

// Wave routes
Wave::routes();
