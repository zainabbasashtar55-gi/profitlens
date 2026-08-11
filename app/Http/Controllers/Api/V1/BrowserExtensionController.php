<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\ExpenseLogged;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowserExtensionController extends Controller
{
    public function expense(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'category' => ['nullable', 'string', 'max:120'],
            'expense_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $categoryId = null;
        if (! empty($data['category'])) {
            $categoryId = ExpenseCategory::firstOrCreate(['name' => $data['category']], ['color' => '#64748b'])->id;
        }

        $expense = Expense::create([
            'expense_category_id' => $categoryId,
            'created_by' => $request->user()->id,
            'vendor' => $data['vendor'] ?? parse_url($data['source_url'] ?? '', PHP_URL_HOST),
            'description' => $data['description'] ?? 'Browser-captured expense',
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
        ]);

        ExpenseLogged::dispatch($expense->load('category'), $request->user());

        return (new ExpenseResource($expense->load(['category', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }
}
