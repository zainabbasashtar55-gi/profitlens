<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpenseRequest;
use App\Http\Requests\Api\V1\UpdateExpenseRequest;
use App\Events\ExpenseLogged;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Expense::query()->with(['category', 'creator']);

        if ($from = $request->query('from')) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('expense_date', '<=', $to);
        }
        if ($categoryId = $request->query('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }
        if ($request->boolean('recurring')) {
            $query->where('recurring', true);
        }
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        return ExpenseResource::collection(
            $query->orderByDesc('expense_date')->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->safe()->except('receipt');
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            // The tenancy FilesystemTenancyBootstrapper auto-prefixes the disk
            // root with "tenant{id}", so paths are already isolated per tenant.
            $path = $file->store('expenses/receipts', config('filesystems.receipts_disk', 'public'));
            $data['receipt_path']          = $path;
            $data['receipt_original_name'] = $file->getClientOriginalName();
        }

        $expense = Expense::create($data);
        $expense->load(['category', 'creator']);

        ExpenseLogged::dispatch($expense, $request->user());

        return (new ExpenseResource($expense))
            ->response()->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load(['category', 'creator']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $data = $request->safe()->except('receipt');

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk(config('filesystems.receipts_disk', 'public'))->delete($expense->receipt_path);
            }
            $file = $request->file('receipt');
            $data['receipt_path']          = $file->store('expenses/receipts', config('filesystems.receipts_disk', 'public'));
            $data['receipt_original_name'] = $file->getClientOriginalName();
        }

        $expense->update($data);

        return new ExpenseResource($expense->load(['category', 'creator']));
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        if ($expense->receipt_path) {
            Storage::disk(config('filesystems.receipts_disk', 'public'))->delete($expense->receipt_path);
        }

        $expense->delete();

        return response()->json(null, 204);
    }
}
