<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Events\InvitationAccepted;
use App\Mail\TenantInvitation;
use App\Models\Invitation;
use App\Models\User;
use App\Services\PlanEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function store(Request $request, PlanEnforcer $plan): RedirectResponse
    {
        $this->authorize('invite-users');

        // Plan limit guard — counts existing users + pending invitations.
        if (! $plan->canAdd('users')) {
            $check = $plan->check('users');
            return back()->withErrors([
                'email' => "Plan limit reached ({$check['current']}/{$check['limit']} users). Upgrade to invite more teammates.",
            ]);
        }

        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'role'  => ['required', Rule::in(['admin', 'member'])],
        ]);

        $invitation = Invitation::create([
            'email'      => $data['email'],
            'role'       => $data['role'],
            'invited_by' => Auth::id(),
        ]);

        Mail::to($invitation->email)->send(new TenantInvitation($invitation));

        return back()->with('status', "Invitation sent to {$invitation->email}");
    }

    public function showAccept(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_if($invitation->isAccepted(), Response::HTTP_GONE, 'Invitation already used.');
        abort_if($invitation->isExpired(), Response::HTTP_GONE, 'Invitation has expired.');

        return view('tenant.invitations.accept', compact('invitation'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_if($invitation->isAccepted(), Response::HTTP_GONE);
        abort_if($invitation->isExpired(), Response::HTTP_GONE);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $invitation->email,
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(), // clicking the invite link is the verification
        ]);

        $user->assignRole($invitation->role);

        $invitation->update(['accepted_at' => now()]);

        // Broadcast so the owner watching /team sees the new row appear live.
        InvitationAccepted::dispatch($user, $invitation->role);

        Auth::login($user);

        return redirect()->route('tenant.dashboard');
    }
}
