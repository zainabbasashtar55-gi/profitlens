<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZapierController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $minimum = $request->float('minimum', 0);

        return response()->json(Sale::query()
            ->with('customer')
            ->where('total_revenue', '>=', $minimum)
            ->latest()
            ->limit($request->integer('limit', 20))
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'sale_date' => $sale->sale_date?->toDateString(),
                'revenue' => (float) $sale->total_revenue,
                'profit' => (float) $sale->total_profit,
                'status' => $sale->status,
                'customer_name' => $sale->customer?->name,
            ]));
    }

    public function expenses(Request $request): JsonResponse
    {
        return response()->json(Expense::query()
            ->with('category')
            ->latest()
            ->limit($request->integer('limit', 20))
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_date' => $expense->expense_date?->toDateString(),
                'amount' => (float) $expense->amount,
                'vendor' => $expense->vendor,
                'description' => $expense->description,
                'category' => $expense->category?->name,
            ]));
    }
}
