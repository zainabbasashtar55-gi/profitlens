<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'vendor'              => ['nullable', 'string', 'max:120'],
            'description'         => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'expense_date'        => ['required', 'date', 'after_or_equal:' . now()->subYears(5)->toDateString(), 'before_or_equal:today'],
            'receipt'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // 5 MB
            'recurring'           => ['boolean'],
            'recurring_period'    => ['nullable', 'required_if:recurring,true', Rule::in(['monthly', 'yearly'])],
        ];
    }
}
