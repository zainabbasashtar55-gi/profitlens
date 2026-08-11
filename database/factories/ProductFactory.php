<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 1, 100);

        return [
            'name'        => fake()->words(3, true),
            'sku'         => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => fake()->sentence(),
            'cost_price'  => $cost,
            'sell_price'  => round($cost * fake()->randomFloat(2, 1.2, 3), 2),
            'active'      => true,
        ];
    }
}
