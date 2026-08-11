<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Stancl\Tenancy\Database\Models\Domain;

class SettingsController extends Controller
{
    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('owner'), 403, 'Only the workspace owner can change these settings.');
    }

    public function index(Request $request): View
    {
        $this->authorizeOwner($request);

        $tenant     = tenant();
        $domain     = $tenant->domains()->first()->domain;
        $tenantRoot = config('app.tenant_domain', 'profitlens.test');
        // The leading subdomain segment ("acme" from "acme.profitlens.test").
        $subdomain  = str_ends_with($domain, '.' . $tenantRoot)
            ? substr($domain, 0, -strlen('.' . $tenantRoot))
            : $domain;

        return view('tenant.settings.index', [
            'tenant'     => $tenant,
            'subdomain'  => $subdomain,
            'tenantRoot' => $tenantRoot,
            'fullDomain' => $domain,
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $oldName = tenant()->name;
        tenant()->update(['name' => $data['name']]);

        activity()
            ->causedBy($request->user())
            ->withProperties(['from' => $oldName, 'to' => $data['name']])
            ->log("renamed workspace from {$oldName} to {$data['name']}");

        return back()->with('status', 'Workspace name updated.');
    }

    public function updateSubdomain(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'subdomain' => ['required', 'string', 'alpha_dash:ascii', 'lowercase', 'max:63'],
        ]);

        $tenantRoot = config('app.tenant_domain', 'profitlens.test');
        $newDomain  = $data['subdomain'] . '.' . $tenantRoot;

        // Bail out if the new subdomain collides with anything else in the
        // global domains table — even a different tenant's existing domain.
        if (Domain::where('domain', $newDomain)
            ->where('tenant_id', '!=', tenant()->id)
            ->exists()) {
            return back()->withErrors([
                'subdomain' => "Subdomain '{$data['subdomain']}' is already taken.",
            ]);
        }

        $tenant    = tenant();
        $existing  = $tenant->domains()->first();
        $oldDomain = $existing->domain;

        if ($oldDomain === $newDomain) {
            return back()->with('status', 'No change — that\'s already your subdomain.');
        }

        $existing->update(['domain' => $newDomain]);

        activity()
            ->causedBy($request->user())
            ->withProperties(['from' => $oldDomain, 'to' => $newDomain])
            ->log("changed subdomain from {$oldDomain} to {$newDomain}");

        // The user is currently on the OLD subdomain. Redirect them to the
        // new one so they keep working. Their session won't carry across
        // domains — they'll have to log in again on the new subdomain.
        $scheme = $request->getScheme();
        $port   = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':' . $port;

        return redirect()->away("{$scheme}://{$newDomain}{$portSuffix}/login")
            ->with('status', "Subdomain changed. Log in on the new address.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        $tenant = tenant();

        // Type-the-name-to-confirm guard — same UX as GitHub repo deletion.
        if ($data['confirmation'] !== $tenant->name) {
            return back()->withErrors([
                'confirmation' => "Confirmation didn't match. Type the workspace name exactly to delete: \"{$tenant->name}\"",
            ]);
        }

        $tenantName = $tenant->name;
        $tenantId   = $tenant->id;

        // Log out the user BEFORE we drop the DB (their session lives in
        // the tenant DB and is about to be deleted).
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Tenant::delete() fires DeletingTenant + TenantDeleted events; the
        // JobPipeline registered in TenancyServiceProvider then runs
        // DeleteDatabase which DROPs the MySQL database.
        $tenant->delete();

        activity()
            ->withProperties(['tenant_id' => $tenantId, 'tenant_name' => $tenantName])
            ->log("deleted workspace {$tenantName}");

        // Bounce them out to the central marketing site.
        $scheme = $request->getScheme();
        $port   = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':' . $port;
        $centralDomain = config('tenancy.central_domains.0', 'profitlens.test');

        return redirect()->away("{$scheme}://{$centralDomain}{$portSuffix}/")
            ->with('status', "Workspace \"{$tenantName}\" has been permanently deleted.");
    }
}
