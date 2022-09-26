<?php

namespace Database\Seeders;

use App\Models\v1\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Inventory::truncate();
        Inventory::factory()
            ->count(20)
            ->create();
    }
}