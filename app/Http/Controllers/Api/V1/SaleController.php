<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSaleRequest;
use App\Http\Requests\Api\V1\UpdateSaleRequest;
use App\Http\Resources\V1\SaleResource;
use App\Events\BigSaleAlert;
use App\Events\SaleRecorded;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\PlanEnforcer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::query()->with(['customer', 'creator']);

        if ($from = $request->query('from')) {
            $query->whereDate('sale_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('sale_date', '<=', $to);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return SaleResource::collection(
            $query->orderByDesc('sale_date')->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreSaleRequest $request, PlanEnforcer $plan): JsonResponse
    {
        $plan->assertCanAdd('sales_per_month');

        $sale = DB::transaction(function () use ($request) {
            $sale = Sale::create([
                'customer_id' => $request->validated('customer_id'),
                'created_by'  => $request->user()->id,
                'sale_date'   => $request->validated('sale_date'),
                'status'      => $request->validated('status'),
                'notes'       => $request->validated('notes'),
            ]);

            foreach ($request->validated('items') as $item) {
                $productName = $item['product_name'] ?? null;

                if (! $productName && ! empty($item['product_id'])) {
                    $productName = Product::find($item['product_id'])?->name ?? 'Unnamed';
                }

                $sale->items()->create([
                    'product_id'   => $item['product_id'] ?? null,
                    'product_name' => $productName,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'unit_cost'    => $item['unit_cost'],
                ]);
            }

            $sale->recomputeTotals();

            return $sale;
        });

        $sale->load(['items', 'customer', 'creator']);

        SaleRecorded::dispatch($sale, $request->user());

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

        return (new SaleResource($sale))->response()->setStatusCode(201);
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load(['items', 'customer', 'creator']));
    }

    public function update(UpdateSaleRequest $request, Sale $sale): SaleResource
    {
        $sale->update($request->validated());

        return new SaleResource($sale->load(['items', 'customer']));
    }

    public function destroy(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $sale->delete();

        return response()->json(null, 204);
    }
}
