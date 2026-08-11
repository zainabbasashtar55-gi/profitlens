<?php

declare(strict_types=1);

use App\Http\Controllers\Central\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Stripe webhook — central, not tenant-scoped. Cashier verifies the
// signature using STRIPE_WEBHOOK_SECRET. No CSRF (Stripe doesn't send one).
Route::post(
    '/stripe/webhook',
    [StripeWebhookController::class, 'handleWebhook']
)->name('cashier.webhook');

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| These routes are served from the central domain(s) listed in
| config/tenancy.php under `central_domains`. They handle the public
| marketing site, tenant signup, and billing — nothing that should
| ever run inside a tenant's database context.
|
*/

$centralDomains = config('tenancy.central_domains');
$primaryDomainIndex = array_search(config('app.tenant_domain'), $centralDomains, true);
$primaryDomainIndex = $primaryDomainIndex === false ? 0 : $primaryDomainIndex;

foreach ($centralDomains as $index => $domain) {
    // Keep the familiar route names on the primary domain. Secondary local
    // aliases need a prefix so route caching can serialize the collection.
    Route::domain($domain)
        ->as($index === $primaryDomainIndex ? '' : "central{$index}.")
        ->group(function () {
        Route::view('/', 'welcome-redesign')->name('landing');

        Route::get('/signup', function (\Illuminate\Http\Request $request) {
            return view('central.signup', ['prefillPlan' => $request->query('plan')]);
        })->name('signup');
        Route::post('/signup', [\App\Http\Controllers\Central\TenantSignupController::class, 'store']);

        Route::view('/billing', 'central.billing')->name('billing');
        });
}
