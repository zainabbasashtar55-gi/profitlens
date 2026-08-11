<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'description'  => $this->description,
            'vendor'       => $this->vendor,
            'amount'       => (float) $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
            'category'     => $this->whenLoaded('category', fn () => $this->category ? [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'color' => $this->category->color,
            ] : null),
            'receipt'      => $this->receipt_path ? [
                'url'           => $this->receiptUrl(),
                'original_name' => $this->receipt_original_name,
            ] : null,
            'recurring'        => (bool) $this->recurring,
            'recurring_period' => $this->recurring_period,
            'created_by'       => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
