<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'sku'         => $this->sku,
            'description' => $this->description,
            'cost_price'  => (float) $this->cost_price,
            'sell_price'  => (float) $this->sell_price,
            'margin_pct'  => round($this->margin(), 2),
            'active'      => $this->active,
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
