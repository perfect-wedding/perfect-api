<?php

namespace Database\Seeders;

use App\Models\v1\Home\Homepage;
use App\Models\v1\Navigation;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Navigation::truncate();
        Homepage::orderBy('priority')->get()->split(2)->each(function ($value, $key) {
            $value->each(function ($item) use ($key) {
                Navigation::create([
                    'location' => 'header',
                    'group' => $key,
                    'active' => true,
                    'navigable_id' => $item->id,
                    'navigable_type' => Homepage::class,
                ]);
            });
        });

        $footer_groups = ['services', 'business', 'solutions_for', 'company'];
        // Spread pages evenly to footer navigation
        Homepage::orderBy('priority')->get()->split(count($footer_groups))->each(function ($value, $key) use ($footer_groups) {
            $value->each(function ($item) use ($key, $footer_groups) {
                Navigation::create([
                    'location' => 'footer',
                    'group' => $footer_groups[$key],
                    'active' => true,
                    'navigable_id' => $item->id,
                    'navigable_type' => Homepage::class,
                ]);
            });
        });
    }
}
