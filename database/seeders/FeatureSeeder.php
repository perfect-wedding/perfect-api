<?php

namespace Database\Seeders;

use App\Models\v1\Company;
use App\Models\v1\Featured;
use App\Models\v1\Inventory;
use App\Models\v1\Plan;
use App\Models\v1\Service;
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
        Company::featured()->each(function ($company) {
            $company->featured_to = null;
        });

        Company::verified()->inRandomOrder()->limit(10)->get()->each(function ($company) {
            $company->featured_to = Carbon::now()->addDays(rand(2, 20));
            $company->save();
        });

        Featured::truncate();
        Company::verified()->whereNotNull('slug')->inRandomOrder()->limit(10)->get()->each(function ($company) {
            $plan = Plan::featureableType('company')->inRandomOrder()->first();

            Featured::create([
                'featureable_id' => $company->id,
                'featureable_type' => Company::class,
                'plan_id' => $plan->id,
                'duration' => rand(10, $plan->duration),
                'tenure' => $plan->tenure,
                'recurring' => [true, false, false, false, true][rand(0, 4)],
                'active' => true,
                'places' => $plan->places,
            ]);
        });

        Service::ownerVerified()->whereNotNull('slug')->inRandomOrder()->limit(10)->get()->each(function ($service) {
            $plan = Plan::featureableType('service')->inRandomOrder()->first();

            Featured::create([
                'featureable_id' => $service->id,
                'featureable_type' => Service::class,
                'plan_id' => $plan->id,
                'duration' => rand(10, $plan->duration),
                'tenure' => $plan->tenure,
                'recurring' => [true, false, false, false, true][rand(0, 4)],
                'active' => true,
                'places' => $plan->meta['places'],
            ]);
        });

        Inventory::ownerVerified()->whereNotNull('slug')->inRandomOrder()->limit(10)->get()->each(function ($inventory) {
            $plan = Plan::featureableType('inventory')->inRandomOrder()->first();

            Featured::create([
                'featureable_id' => $inventory->id,
                'featureable_type' => Inventory::class,
                'plan_id' => $plan->id,
                'duration' => rand(10, $plan->duration),
                'tenure' => $plan->tenure,
                'recurring' => [true, false, false, false, true][rand(0, 4)],
                'active' => true,
                'places' => $plan->meta['places'],
            ]);
        });
    }
}
