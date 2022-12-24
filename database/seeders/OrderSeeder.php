<?php

namespace Database\Seeders;

use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Order;
use App\Models\v1\Service;
use App\Models\v1\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orders = [];
        foreach (range(0, 220) as $key => $value) {
            $orderable = [
                Service::inRandomOrder()->first(),
                Inventory::inRandomOrder()->first(),
            ][rand(0, 1)];

            if ($orderable && $orderable->company) {
                $orders[] = [
                    'user_id' => User::inRandomOrder()->first()->id,
                    'company_id' => $orderable->company->id,
                    'company_type' => Company::class,
                    'orderable_type' => get_class($orderable),
                    'orderable_id' => $orderable->id,
                    'code' => 'ODR-'.fake()->unixTime().'-V'.$value,
                    'destination' => fake()->address(),
                    'status' => ['requesting', 'pending', 'in-progress', 'delivered', 'completed'][rand(0, 3)],
                    'amount' => rand(100, 500),
                ];
            }
        }

        Order::insert($orders);
    }
}
