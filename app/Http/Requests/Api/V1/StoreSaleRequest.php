<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin', 'member']) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id'         => ['nullable', 'exists:customers,id'],
            'sale_date'           => ['required', 'date', 'after_or_equal:' . now()->subYears(5)->toDateString(), 'before_or_equal:today'],
            'status'              => ['required', Rule::in(['draft', 'paid', 'refunded'])],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:120'],
            'items.*.quantity'    => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'items.*.unit_cost'   => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
