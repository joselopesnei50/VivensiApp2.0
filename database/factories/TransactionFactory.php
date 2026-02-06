<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Tenant;
use App\Models\FinancialCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'pending',
        ];
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'income',
            ];
        });
    }

    /**
     * Indicate that the transaction is expense.
     */
    public function expense()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'expense',
            ];
        });
    }

    /**
     * Indicate that the transaction is approved.
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }
}
