<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSaleRequest;
use App\Events\BigSaleAlert;
use App\Events\ProfitGoalProgress;
use App\Events\SaleRecorded;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProfitGoal;
use App\Models\Sale;
use App\Models\User;
use App\Services\PlanEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sale::query()->with(['customer', 'creator']);
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('sale_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('sale_date', '<=', $to);
        }

        return view('tenant.sales.index', [
            'sales' => $query->orderByDesc('sale_date')->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }

    public function create(): View
    {
        return view('tenant.sales.form', [
            'customers' => Customer::orderBy('name')->get(),
            'products'  => Product::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSaleRequest $request, PlanEnforcer $plan): RedirectResponse
    {
        if (! $plan->canAdd('sales_per_month')) {
            $check = $plan->check('sales_per_month');
            return back()->withErrors([
                'plan' => "Plan limit reached ({$check['current']}/{$check['limit']} sales this month). Upgrade to record more.",
            ])->withInput();
        }

        $sale = DB::transaction(function () use ($request) {
            $sale = Sale::create([
                'customer_id' => $request->validated('customer_id'),
                'created_by'  => $request->user()->id,
                'sale_date'   => $request->validated('sale_date'),
                'status'      => $request->validated('status'),
                'notes'       => $request->validated('notes'),
            ]);

            foreach ($request->validated('items') as $item) {
                $sale->items()->create([
                    'product_id'   => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? Product::find($item['product_id'])?->name ?? 'Unnamed',
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'unit_cost'    => $item['unit_cost'],
                ]);
            }

            $sale->recomputeTotals();

            return $sale;
        });

        // Broadcast to every active dashboard so KPIs tick + toast slides in.
        SaleRecorded::dispatch($sale->load('customer'), $request->user());

        // Big-sale celebration: if this sale beats the previous high this month,
        // ping every owner privately so they get a confetti toast.
        $bestThisMonth = Sale::whereBetween('sale_date', [
                now()->startOfMonth(), now()->endOfMonth(),
            ])
            ->where('id', '!=', $sale->id)
            ->where('status', 'paid')
            ->max('total_revenue');

        if ((float) $sale->total_revenue > (float) ($bestThisMonth ?? 0) && $sale->status === 'paid') {
            foreach (User::role('owner')->get() as $owner) {
                BigSaleAlert::dispatch($sale, $owner);
            }
        }

        // Advance the profit-goal progress bar on every open dashboard.
        if ($goal = ProfitGoal::currentMonthly()) {
            ProfitGoalProgress::dispatch((string) tenant('id'), $goal->progress());
        }

        return redirect()->route('sales.index')->with('status', "Sale #{$sale->id} recorded (\${$sale->total_revenue} revenue, \${$sale->total_profit} profit).");
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);
        $sale->delete();

        return redirect()->route('sales.index')->with('status', 'Sale deleted.');
    }
}
