<?php

namespace Database\Factories;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost  = fake()->randomFloat(2, 1, 50);
        $price = round($cost * fake()->randomFloat(2, 1.3, 2.5), 2);
        $qty   = fake()->numberBetween(1, 5);

        return [
            'product_name' => fake()->words(2, true),
            'quantity'     => $qty,
            'unit_price'   => $price,
            'unit_cost'    => $cost,
            'line_total'   => $qty * $price,
            'line_profit'  => $qty * ($price - $cost),
        ];
    }
}
