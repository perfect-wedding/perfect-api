<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = new Generator();
        \App\Models\v1\User::insert([
            [
                'firstname' => 'King',
                'lastname' => 'Bankole',
                'username' => 'banki',
                'address' => '31 Somewhere in Kaduna',
                'dob' => Carbon::now()->subYears(43),
                'email' => 'bankole@gmail.com',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => \Str::random(10),
                'role' => 'admin',
                'privileges' => json_encode(['admin']),
                'type' => ['individual', 'company'][rand(0, 1)],
            ],
        ]);
        \App\Models\v1\User::factory(30)->create();
        $this->call([
            CategorySeeder::class,
            CompanySeeder::class,
            ServiceSeeder::class,
            InventorySeeder::class,
            MarketSeeder::class,
            OfferSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
            // SettingsSeeder::class,
            PlanSeeder::class,
            WalletSeeder::class,
        ]);
    }
}
