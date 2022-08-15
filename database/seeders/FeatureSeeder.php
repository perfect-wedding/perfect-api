<?php

namespace Database\Seeders;

use App\Models\v1\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Company::inRandomOrder()->limit(10)->get()->each(function ($company) {
            $company->featured_to = Carbon::now()->addDays(rand(2, 20));
            $company->save();
        });
    }
}
