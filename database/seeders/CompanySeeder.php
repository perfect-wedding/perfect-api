<?php

namespace Database\Seeders;

use App\Models\v1\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\v1\Company::upsert([
        //     [
        //         'slug' => Str::of('Free Forever')->slug(),
        //         'name'=> 'Social Forever LTD',
        //         'user_id' => User::inRandomOrder()->first()->id,
        //         'status'=> 'verified',
        //     ],
        //     [
        //         'slug' => Str::of('Enterprise')->slug(),
        //         'name'=> 'Feindi Enterprise',
        //         'user_id' => User::inRandomOrder()->first()->id,
        //         'status'=> 'verified',
        //     ],
        // ]);
        // Company::truncate();
        Company::factory()
            ->count(20)
            ->create();
    }
}
