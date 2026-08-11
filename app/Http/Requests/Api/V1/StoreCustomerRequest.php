<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'admin', 'member']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone'   => ['nullable', 'string', 'max:40'],
            'notes'   => ['nullable', 'string', 'max:2000'],
        ];
    }
}
