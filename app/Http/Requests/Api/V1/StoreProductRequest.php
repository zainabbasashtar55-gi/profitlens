<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:120'],
            'sku'         => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price'  => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'sell_price'  => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'active'      => ['boolean'],
        ];
    }
}
