<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => \App\Models\Transaction::query()->inRandomOrder()->value('id'), // Ambil ID acak dari tabel transaction
        'menu_id' => \App\Models\Menu::query()->inRandomOrder()->value('id'), // Ambil ID acak dari tabel item
        'quantity' => $this->faker->numberBetween(1, 10),
        'price' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
