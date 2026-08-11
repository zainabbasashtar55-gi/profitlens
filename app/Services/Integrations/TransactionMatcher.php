<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Expense;
use App\Models\ImportedTransaction;
use App\Models\Sale;

class TransactionMatcher
{
    public function match(ImportedTransaction $transaction): ImportedTransaction
    {
        if ($transaction->kind === 'income') {
            $sale = Sale::query()
                ->whereDate('sale_date', $transaction->transaction_date)
                ->where('total_revenue', abs((float) $transaction->amount))
                ->first();

            if ($sale) {
                $transaction->update([
                    'match_type' => 'sale_amount_date',
                    'matched_sale_id' => $sale->id,
                ]);
            }

            return $transaction;
        }

        $expense = Expense::query()
            ->whereDate('expense_date', $transaction->transaction_date)
            ->where('amount', abs((float) $transaction->amount))
            ->first();

        if ($expense) {
            $transaction->update([
                'match_type' => 'expense_amount_date',
                'matched_expense_id' => $expense->id,
            ]);
        }

        return $transaction;
    }
}
