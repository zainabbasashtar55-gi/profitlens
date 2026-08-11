<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1 as Api;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CustomerController as WebCustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EmailVerificationController;
use App\Http\Controllers\Tenant\ExpenseController as WebExpenseController;
use App\Http\Controllers\Tenant\InsightsController;
use App\Http\Controllers\Tenant\InvitationController;
use App\Http\Controllers\Tenant\InvoiceController as WebInvoiceController;
use App\Http\Controllers\Tenant\PasswordResetController;
use App\Http\Controllers\Tenant\ProductController as WebProductController;
use App\Http\Controllers\Tenant\ProfitGoalController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\SaleController as WebSaleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\TeamController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

// Web UI (session-based auth)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('tenant.login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('tenant.logout');

    // Password reset (guest only) — throttled to prevent enumeration/spam.
    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    // Email verification (auth required for notice + resend; signed URL for verify itself).
    Route::middleware('auth')->group(function () {
        Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });

    Route::get('/invitations/{token}', [InvitationController::class, 'showAccept'])
        ->name('invitations.accept.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])
        ->name('invitations.accept');

    // ── Customer-facing magic-link invoice ───────────────────────────────────
    // No auth: the public_token is the credential. Tenancy still initialises
    // (route is inside the tenant middleware group) so we hit the right DB.
    Route::get('/pay/{token}',        [PublicInvoiceController::class, 'show'])->name('public.invoice.show');
    Route::get('/pay/{token}/print',  [PublicInvoiceController::class, 'print'])->name('public.invoice.print');
    Route::post('/pay/{token}/pay',   [PublicInvoiceController::class, 'pay'])->middleware('throttle:6,1')->name('public.invoice.pay');

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('tenant.dashboard');
        Route::post('/invitations', [InvitationController::class, 'store'])
            ->name('invitations.store');

        // Reverb/Echo channel auth — needs to be tenant-scoped so the
        // routes/channels.php callbacks can call tenant() correctly.
        Route::post('/broadcasting/auth', \Illuminate\Broadcasting\BroadcastController::class . '@authenticate');

        // Domain CRUD UI
        Route::resource('customers', WebCustomerController::class)->except(['show']);
        Route::resource('products', WebProductController::class)->except(['show']);
        Route::resource('sales', WebSaleController::class)->except(['edit', 'update']);
        Route::resource('expenses', WebExpenseController::class)->except(['show']);

        // AI Insights — forecast, anomalies, profit-goal tracker, and chat
        Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
        Route::post('/insights/chat', [InsightsController::class, 'chat'])->middleware('throttle:30,1')->name('insights.chat');
        Route::post('/goals', [ProfitGoalController::class, 'store'])->name('goals.store');

        // Invoices — resource CRUD plus three action endpoints
        Route::resource('invoices', WebInvoiceController::class);
        Route::get( '/invoices/{invoice}/print',     [WebInvoiceController::class, 'print'])->name('invoices.print');
        Route::post('/invoices/{invoice}/send',      [WebInvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/mark-paid', [WebInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

        // Billing (owner-only)
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

        // Workspace settings (owner-only)
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings/name', [SettingsController::class, 'updateName'])->name('settings.update-name');
        Route::patch('/settings/subdomain', [SettingsController::class, 'updateSubdomain'])->name('settings.update-subdomain');
        Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');

        // Team management
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/team/{user}', [TeamController::class, 'show'])->name('team.show');
        Route::patch('/team/{user}/role', [TeamController::class, 'updateRole'])->name('team.update-role');
        Route::patch('/team/{user}/transfer-ownership', [TeamController::class, 'transferOwnership'])->name('team.transfer-ownership');
        Route::delete('/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::delete('/team/invitations/{invitation}', [TeamController::class, 'revokeInvitation'])->name('team.revoke-invitation');

        // Reports
        Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('/reports/sales.csv', [ReportController::class, 'salesCsv'])->name('reports.sales.csv');
        Route::get('/reports/expenses.csv', [ReportController::class, 'expensesCsv'])->name('reports.expenses.csv');
        Route::get('/activity', [ReportController::class, 'activity'])->name('activity.index');
    });
});

// API V1 (token-based auth via Sanctum)
Route::middleware([
    'api',
    'throttle:api-public',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api/v1')->name('api.v1.')->group(function () {

    // Auth — login is public, everything else needs a Sanctum token.
    Route::post('/auth/login', [Api\AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [Api\AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [Api\AuthController::class, 'logout'])->name('auth.logout');

        Route::apiResource('customers', Api\CustomerController::class);
        Route::apiResource('products',  Api\ProductController::class);
        Route::apiResource('sales',     Api\SaleController::class);
        Route::apiResource('expenses',  Api\ExpenseController::class);

        Route::get('/analytics/dashboard',    [Api\AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
        Route::get('/analytics/profit-loss',  [Api\AnalyticsController::class, 'profitLoss'])->name('analytics.profit-loss');

        Route::get('/openapi.json', [Api\OpenApiController::class, 'show'])->name('openapi');

        Route::apiResource('webhook-endpoints', Api\WebhookEndpointController::class)
            ->except(['show'])
            ->parameters(['webhook-endpoints' => 'webhookEndpoint']);
        Route::post('/webhook-endpoints/{webhookEndpoint}/rotate-secret', [Api\WebhookEndpointController::class, 'rotateSecret'])
            ->name('webhook-endpoints.rotate-secret');

        Route::get('/integrations', [Api\IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('/integrations/plaid/link-token', [Api\PlaidController::class, 'linkToken'])->name('integrations.plaid.link-token');
        Route::post('/integrations/plaid/exchange-token', [Api\PlaidController::class, 'exchangeToken'])->name('integrations.plaid.exchange-token');
        Route::post('/integrations/plaid/import', [Api\PlaidController::class, 'import'])->name('integrations.plaid.import');
        Route::get('/integrations/google-sheets/export', [Api\GoogleSheetsController::class, 'export'])->name('integrations.google-sheets.export');
        Route::post('/integrations/google-sheets/import', [Api\GoogleSheetsController::class, 'import'])->name('integrations.google-sheets.import');
        Route::post('/integrations/browser-extension/expense', [Api\BrowserExtensionController::class, 'expense'])->name('integrations.browser-extension.expense');
        Route::get('/integrations/zapier/triggers/sales', [Api\ZapierController::class, 'sales'])->name('integrations.zapier.sales');
        Route::get('/integrations/zapier/triggers/expenses', [Api\ZapierController::class, 'expenses'])->name('integrations.zapier.expenses');
        Route::post('/integrations/slack/command', [Api\BotCommandController::class, 'slack'])->middleware('throttle:30,1')->name('integrations.slack.command');
        Route::post('/integrations/discord/command', [Api\BotCommandController::class, 'discord'])->middleware('throttle:30,1')->name('integrations.discord.command');
    });
});
