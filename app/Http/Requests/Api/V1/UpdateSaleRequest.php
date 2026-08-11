<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'nullable', 'exists:customers,id'],
            'sale_date'   => ['sometimes', 'date'],
            'status'      => ['sometimes', Rule::in(['draft', 'paid', 'refunded'])],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }
}
