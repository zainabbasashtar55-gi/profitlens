<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('tenant.team.index', [
            'users'               => $query->orderBy('id')->get(),
            'pendingInvitations'  => Invitation::with('invitedBy')
                ->whereNull('accepted_at')
                ->orderByDesc('created_at')
                ->get(),
            'availableRoles'      => ['owner', 'admin', 'member'],
            'search'              => $search,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        // Stats: sales recorded + expenses logged by this user.
        $salesCount    = Sale::where('created_by', $user->id)->count();
        $salesRevenue  = (float) Sale::where('created_by', $user->id)->where('status', 'paid')->sum('total_revenue');
        $expensesCount = Expense::where('created_by', $user->id)->count();
        $expensesTotal = (float) Expense::where('created_by', $user->id)->sum('amount');

        // Recent activity caused by this user.
        $activities = Activity::where('causer_id', $user->id)
            ->where('causer_type', User::class)
            ->latest()
            ->limit(20)
            ->get();

        return view('tenant.team.show', [
            'user'          => $user->load('roles'),
            'salesCount'    => $salesCount,
            'salesRevenue'  => $salesRevenue,
            'expensesCount' => $expensesCount,
            'expensesTotal' => $expensesTotal,
            'activities'    => $activities,
            'canManage'     => $request->user()->hasAnyRole(['owner', 'admin'])
                                && $request->user()->id !== $user->id
                                && (! $user->hasRole('owner') || $request->user()->hasRole('owner')),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeRoleChange($request, $user);

        $data = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'member'])],
        ]);

        // Don't let the last owner demote themselves — workspace must have ≥1 owner.
        if ($user->hasRole('owner') && $data['role'] !== 'owner' && User::role('owner')->count() <= 1) {
            return back()->withErrors(['role' => 'Cannot demote the only owner. Promote someone else to owner first.']);
        }

        $oldRoles = $user->roles->pluck('name')->toArray();
        $user->syncRoles([$data['role']]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['old' => $oldRoles, 'new' => $data['role']])
            ->log("changed {$user->name}'s role to {$data['role']}");

        return back()->with('status', "{$user->name} is now a {$data['role']}.");
    }

    /**
     * Transfer ownership to another user. The current owner becomes an admin
     * (so they're not orphaned). This is the safe way for an owner to leave.
     */
    public function transferOwnership(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasRole('owner'), 403, 'Only an owner can transfer ownership.');

        if ($user->id === $actor->id) {
            return back()->withErrors(['user' => "You're already the owner."]);
        }

        $data = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if ($data['confirmation'] !== $user->email) {
            return back()->withErrors([
                'confirmation' => "Type the new owner's email exactly to confirm: {$user->email}",
            ]);
        }

        // Promote target to owner, demote current owner to admin in a single
        // operation so the workspace is never left ownerless.
        \DB::transaction(function () use ($user, $actor) {
            $user->syncRoles(['owner']);
            $actor->syncRoles(['admin']);
        });

        activity()
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties(['from' => $actor->email, 'to' => $user->email])
            ->log("transferred workspace ownership from {$actor->name} to {$user->name}");

        return redirect()->route('team.index')
            ->with('status', "Ownership transferred to {$user->name}. You're now an admin.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => "You can't remove yourself."]);
        }

        if ($user->hasRole('owner') && User::role('owner')->count() <= 1) {
            return back()->withErrors(['user' => 'Cannot remove the only owner.']);
        }

        if ($user->hasRole('owner') && ! $request->user()->hasRole('owner')) {
            return back()->withErrors(['user' => 'Only an owner can remove another owner.']);
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('team.index')->with('status', "{$user->name} removed from workspace.");
    }

    public function revokeInvitation(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $invitation->delete();

        return back()->with('status', "Invitation to {$invitation->email} revoked.");
    }

    private function authorizeRoleChange(Request $request, User $target): void
    {
        $actor = $request->user();
        abort_unless($actor->hasAnyRole(['owner', 'admin']), 403);

        if ($target->hasRole('owner') && ! $actor->hasRole('owner')) {
            abort(403, 'Only an owner can change an owner\'s role.');
        }
    }
}
