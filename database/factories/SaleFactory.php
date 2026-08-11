<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_date'     => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'status'        => fake()->randomElement(['paid', 'draft', 'refunded']),
            'total_revenue' => 0,
            'total_cost'    => 0,
            'total_profit'  => 0,
            'notes'         => fake()->optional()->sentence(),
        ];
    }
}
