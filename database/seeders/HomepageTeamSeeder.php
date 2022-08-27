<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageTeam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomepageTeamSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageTeam::factory(6)->create();
    }
}
