<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sale_date'      => $this->sale_date?->toDateString(),
            'status'         => $this->status,
            'customer'       => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id'      => $this->customer->id,
                'name'    => $this->customer->name,
                'company' => $this->customer->company,
            ] : null),
            'totals'         => [
                'revenue' => (float) $this->total_revenue,
                'cost'    => (float) $this->total_cost,
                'profit'  => (float) $this->total_profit,
                'margin'  => round($this->profitMargin(), 2),
            ],
            'items'          => SaleItemResource::collection($this->whenLoaded('items')),
            'notes'          => $this->notes,
            'created_by'     => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
