<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\ExpenseLogged;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IntegrationConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleSheetsController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->endOfMonth()->toDateString());

        return response()->json([
            'sales' => \App\Models\Sale::query()
                ->whereBetween('sale_date', [$from, $to])
                ->latest('sale_date')
                ->get()
                ->map(fn ($sale) => [
                    'id' => $sale->id,
                    'date' => $sale->sale_date?->toDateString(),
                    'status' => $sale->status,
                    'revenue' => (float) $sale->total_revenue,
                    'cost' => (float) $sale->total_cost,
                    'profit' => (float) $sale->total_profit,
                    'notes' => $sale->notes,
                ]),
            'expenses' => Expense::query()
                ->whereBetween('expense_date', [$from, $to])
                ->latest('expense_date')
                ->get()
                ->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'date' => $expense->expense_date?->toDateString(),
                    'vendor' => $expense->vendor,
                    'description' => $expense->description,
                    'amount' => (float) $expense->amount,
                    'category' => $expense->category?->name,
                ]),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $data = $request->validate([
            'spreadsheet_id' => ['nullable', 'string', 'max:160'],
            'expenses' => ['required', 'array'],
            'expenses.*.date' => ['required', 'date'],
            'expenses.*.vendor' => ['nullable', 'string', 'max:120'],
            'expenses.*.description' => ['required', 'string', 'max:255'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0'],
            'expenses.*.category' => ['nullable', 'string', 'max:120'],
        ]);

        $connection = IntegrationConnection::updateOrCreate(
            ['provider' => 'google_sheets', 'external_id' => $data['spreadsheet_id'] ?? 'manual-sync'],
            ['name' => 'Google Sheets', 'settings' => ['spreadsheet_id' => $data['spreadsheet_id'] ?? null]]
        );

        $expenses = collect($data['expenses'])->map(function (array $row) use ($request) {
            $categoryId = null;
            if (! empty($row['category'])) {
                $categoryId = ExpenseCategory::firstOrCreate(['name' => $row['category']], ['color' => '#64748b'])->id;
            }

            $expense = Expense::create([
                'expense_category_id' => $categoryId,
                'created_by' => $request->user()->id,
                'vendor' => $row['vendor'] ?? null,
                'description' => $row['description'],
                'amount' => $row['amount'],
                'expense_date' => $row['date'],
            ]);

            ExpenseLogged::dispatch($expense->load('category'), $request->user());

            return $expense;
        });

        $connection->update(['last_synced_at' => now()]);

        return response()->json(['data' => $expenses], 201);
    }
}
