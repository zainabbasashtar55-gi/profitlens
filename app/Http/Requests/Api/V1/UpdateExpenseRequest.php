<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['sometimes', 'nullable', 'exists:expense_categories,id'],
            'vendor'              => ['sometimes', 'nullable', 'string', 'max:120'],
            'description'         => ['sometimes', 'required', 'string', 'max:255'],
            'amount'              => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'expense_date'        => ['sometimes', 'date'],
            'receipt'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'recurring'           => ['sometimes', 'boolean'],
            'recurring_period'    => ['nullable', Rule::in(['monthly', 'yearly'])],
        ];
    }
}
