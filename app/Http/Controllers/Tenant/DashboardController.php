<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\ProfitGoal;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsController $analytics)
    {
        $goal = ProfitGoal::currentMonthly();

        return view('tenant.dashboard', [
            'tenant'             => tenant(),
            'users'              => User::with('roles')->orderBy('id')->get(),
            'pendingInvitations' => Invitation::with('invitedBy')
                ->whereNull('accepted_at')
                ->orderByDesc('created_at')
                ->get(),
            'analytics'    => $analytics->dashboard($request)->getData(true),
            'goal'         => $goal,
            'goalProgress' => $goal?->progress(),
        ]);
    }
}
