<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Events\ExpenseLogged;
use App\Events\LowCashWarning;
use App\Http\Requests\Api\V1\StoreExpenseRequest;
use App\Http\Requests\Api\V1\UpdateExpenseRequest;
use App\Jobs\ProcessReceiptOcr;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Services\Ai\ExpenseCategorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::query()->with(['category', 'creator']);
        if ($categoryId = $request->query('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }
        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")->orWhere('vendor', 'like', "%{$search}%"));
        }

        return view('tenant.expenses.index', [
            'expenses'   => $query->orderByDesc('expense_date')->paginate(20)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filters'    => $request->only(['category_id', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('tenant.expenses.form', [
            'expense'    => new Expense(['expense_date' => now()->toDateString()]),
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function store(StoreExpenseRequest $request, ExpenseCategorizer $categorizer): RedirectResponse
    {
        $data = $request->safe()->except('receipt');
        $data['created_by'] = $request->user()->id;

        $hasReceipt = $request->hasFile('receipt');
        if ($hasReceipt) {
            $file = $request->file('receipt');
            $data['receipt_path']          = $file->store('expenses/receipts', config('filesystems.receipts_disk'));
            $data['receipt_original_name'] = $file->getClientOriginalName();
        }

        // Smart categorization: if the user didn't pick a category, infer one.
        // Best-effort — a failure here must never block logging the expense.
        if (empty($data['expense_category_id'])) {
            try {
                $category = $categorizer->categorize(
                    $data['description'] ?? '',
                    $data['vendor'] ?? null,
                    isset($data['amount']) ? (float) $data['amount'] : null,
                );
                if ($category) {
                    $data['expense_category_id'] = $category->id;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $expense = Expense::create($data);

        ExpenseLogged::dispatch($expense->load('category'), $request->user());

        // Kick off receipt OCR pipeline. The job streams progress over the
        // uploader's private user.{id} channel — the expense form / dashboard
        // displays a live progress bar.
        if ($hasReceipt) {
            ProcessReceiptOcr::dispatch(
                $expense->id,
                $request->user()->id,
                (string) Str::uuid(),
            );
        }

        // Cashflow alert: net profit this month dropped below threshold?
        $this->checkCashflowAlert();

        return redirect()->route('expenses.index')->with(
            'status',
            $hasReceipt
                ? 'Expense logged. Receipt OCR is running — you’ll see progress in the corner.'
                : 'Expense logged.',
        );
    }

    private function checkCashflowAlert(int $threshold = 0): void
    {
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $profit   = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('total_profit');
        $expenses = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');
        $net      = $profit - $expenses;

        if ($net < $threshold) {
            LowCashWarning::dispatch($net, (float) $threshold);
        }
    }

    public function edit(Expense $expense): View
    {
        return view('tenant.expenses.form', [
            'expense'    => $expense,
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->safe()->except('receipt');

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk(config('filesystems.receipts_disk'))->delete($expense->receipt_path);
            }
            $file = $request->file('receipt');
            $data['receipt_path']          = $file->store('expenses/receipts', config('filesystems.receipts_disk'));
            $data['receipt_original_name'] = $file->getClientOriginalName();
        }

        $expense->update($data);

        return redirect()->route('expenses.index')->with('status', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        if ($expense->receipt_path) {
            Storage::disk(config('filesystems.receipts_disk'))->delete($expense->receipt_path);
        }
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Expense deleted.');
    }
}
