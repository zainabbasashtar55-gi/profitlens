<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor'       => fake()->company(),
            'description'  => fake()->sentence(4),
            'amount'       => fake()->randomFloat(2, 5, 5000),
            'expense_date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'recurring'    => false,
        ];
    }
}
