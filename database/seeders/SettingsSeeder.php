<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::truncate();
        Settings::insert([
            [
                'key' => 'logo',
                'value' => asset('images/bank/logo.png'),
            ], [
                'key' => 'home_banner',
                'value' => asset('images/bank/imagese.png'),
            ], [
                'key' => 'auth_banner',
                'value' => asset('images/bank/mrb12.jpg'),
            ], [
                'key' => 'newsletter_banner',
                'value' => asset('images/bank/perfe1.png'),
            ], [
                'key' => 'home_banner_tagline',
                'value' => 'BUILDING LIFE EXPERIENCES WITH PEOPLE YOU LOVE',
            ], [
                'key' => 'home_banner_subline',
                'value' => 'A Place For People To Find Verified And Trusted Wedding Industry Service Providers',
            ],
        ]);
    }
}
