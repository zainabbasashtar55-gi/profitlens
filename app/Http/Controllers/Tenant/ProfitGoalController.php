<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Events\ProfitGoalProgress;
use App\Http\Controllers\Controller;
use App\Models\ProfitGoal;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfitGoalController extends Controller
{
    /**
     * Set (or update) the net-profit goal for the current month.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $goal = ProfitGoal::updateOrCreate(
            [
                'period_type' => 'month',
                'period_start' => CarbonImmutable::now()->startOfMonth()->toDateString(),
            ],
            [
                'target_amount' => $validated['target_amount'],
                'created_by' => $request->user()->id,
            ],
        );

        // Push fresh progress to any open dashboards.
        ProfitGoalProgress::dispatch((string) tenant('id'), $goal->progress());

        return redirect()->back()->with('status', 'Profit goal set: $'.number_format((float) $goal->target_amount, 0).' for '.$goal->periodStart()->format('F').'.');
    }
}
