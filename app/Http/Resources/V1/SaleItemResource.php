<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->product_name,
            'quantity'     => $this->quantity,
            'unit_price'   => (float) $this->unit_price,
            'unit_cost'    => (float) $this->unit_cost,
            'line_total'   => (float) $this->line_total,
            'line_profit'  => (float) $this->line_profit,
        ];
    }
}
