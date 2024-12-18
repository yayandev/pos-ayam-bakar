<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
        ]);

        Menu::factory(50)->create();

        Transaction::factory(200)->create();

        TransactionItem::factory(300)->create();
    }
}
