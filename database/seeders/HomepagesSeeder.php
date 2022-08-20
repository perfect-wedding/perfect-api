<?php

namespace Database\Seeders;

use App\Models\v1\Home\Homepage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HomepagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Homepage::truncate();

        Homepage::insert([
            'title' => 'Homepage',
            'meta' => '',
            'slug' => 'homepage',
            'default' => true,
        ]);
        Schema::enableForeignKeyConstraints();
    }
}