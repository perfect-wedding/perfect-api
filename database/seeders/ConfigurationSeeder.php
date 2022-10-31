<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\v1\Configuration::truncate();
        \App\Models\v1\Configuration::insert([
            [
                'key' => 'gift_shop_welcome_title',
                'title' => 'Gift Shop Welcome Title',
                'value' => 'Welcome to the Giftshop',
                'type' => 'string',
                'count' => null,
                'max' => null,
            ], [
                'key' => 'gift_shop_welcome_msg',
                'title' => 'Gift Shop Welcome Message',
                'value' => 'Find the perfect gift for the perfect person',
                'type' => 'string',
                'count' => null,
                'max' => null,
            ], [
                'key' => 'gift_shop_welcome_imgs',
                'title' => 'Gift Shop Welcome Images',
                'type' => 'files',
                'value' => null,
                'count' => 5,
                'max' => '1024',
            ], [
                'key' => 'gift_shop_explore_title',
                'title' => 'Gift Shop Explore Title',
                'type' => 'string',
                'value' => 'Who gets a gift today?',
                'count' => null,
                'max' => null,
            ], [
                'key' => 'gift_shop_explore_msg',
                'title' => 'Gift Shop Explore Message',
                'value' => 'Make someone smile, get them the perfect gift!',
                'type' => 'string',
                'count' => null,
                'max' => null,
            ], [
                'key' => 'gift_shop_explore_bg',
                'title' => 'Gift Shop Explore Background Image',
                'type' => 'file',
                'value' => null,
                'count' => null,
                'max' => '1024',
            ],
        ]);
    }
}
