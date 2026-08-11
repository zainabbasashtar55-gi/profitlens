<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function profitLoss(Request $request, AnalyticsController $api)
    {
        $payload = $api->profitLoss($request)->getData(true);

        return view('tenant.reports.profit-loss', [
            'pl'   => $payload,
            'from' => $request->query('from') ?: Carbon::now()->startOfMonth()->toDateString(),
            'to'   => $request->query('to') ?: Carbon::now()->endOfMonth()->toDateString(),
        ]);
    }

    public function salesCsv(): StreamedResponse
    {
        $filename = 'sales-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'date', 'status', 'customer', 'revenue', 'cost', 'profit', 'notes']);

            Sale::with('customer')->orderBy('sale_date')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->id,
                        $s->sale_date?->toDateString(),
                        $s->status,
                        $s->customer?->name,
                        $s->total_revenue,
                        $s->total_cost,
                        $s->total_profit,
                        $s->notes,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function activity(Request $request): View
    {
        $query = Activity::with('causer')->latest();

        if ($type = $request->query('type')) {
            $query->where('subject_type', 'like', "%{$type}%");
        }
        if ($user = $request->query('user_id')) {
            $query->where('causer_id', $user);
        }

        return view('tenant.activity.index', [
            'activities' => $query->paginate(30)->withQueryString(),
            'filters'    => $request->only(['type', 'user_id']),
        ]);
    }

    public function expensesCsv(): StreamedResponse
    {
        $filename = 'expenses-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'date', 'category', 'vendor', 'description', 'amount', 'recurring']);

            Expense::with('category')->orderBy('expense_date')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [
                        $e->id,
                        $e->expense_date?->toDateString(),
                        $e->category?->name,
                        $e->vendor,
                        $e->description,
                        $e->amount,
                        $e->recurring ? 'yes' : 'no',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
