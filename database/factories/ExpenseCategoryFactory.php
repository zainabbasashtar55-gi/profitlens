<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'  => fake()->unique()->randomElement(['Rent', 'Payroll', 'Software', 'Marketing', 'Travel', 'Utilities', 'Office']),
            'color' => fake()->hexColor(),
        ];
    }
}
