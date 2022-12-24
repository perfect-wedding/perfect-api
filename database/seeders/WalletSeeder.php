<?php

namespace Database\Seeders;

use App\Models\v1\User;
use App\Models\v1\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $funds = [];
        foreach (range(0, User::count() * 10) as $key => $value) {
            $source = [
                ['s' => 'Paystack', 'a' => rand(1000, 99999), 't' => 'credit', 'd' => 'Direct wallet funding'],
                ['s' => 'System', 'a' => rand(1000, 99999), 't' => 'credit', 'd' => 'Conscierge service funding'],
                ['s' => 'Order', 'a' => rand(1000, 99999), 't' => 'debit', 'd' => 'Payment for order'],
                ['s' => 'Rebound', 'a' => rand(1000, 99999), 't' => 'credit', 'd' => 'Rebound from rejected order'],
            ][rand(0, 3)];

            $funds[] = [
                'user_id' => User::inRandomOrder()->first()->id,
                'amount' => $source['a'],
                'source' => $source['s'],
                'detail' => $source['d'],
                'type' => $source['t'],
                'reference' => 'TRX-'.fake()->unixTime().'-R'.$value,
            ];
        }

        Wallet::insert($funds);
    }
}
