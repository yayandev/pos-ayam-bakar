<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'total_amount' => $this->faker->numberBetween(10000, 100000),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'customer_name' => $this->faker->name,
            'payment_method' => $this->faker->randomElement(['cash', 'cashless']),
            'money_paid' => $this->faker->numberBetween(0, $this->faker->numberBetween(10000, 100000)),
            'code_transaction' => 'NBS/' . date('Y') . '/' . $this->faker->unique()->numberBetween(1, 99999)
        ];
    }
}
