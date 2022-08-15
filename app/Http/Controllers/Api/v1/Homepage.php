<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Testimonial;

class Homepage extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::get();
        $app_data = [
            'page' => 'home.index',
            'title' => 'Homepage',
            'markets' => Category::where('type', 'market')->limit(6)->inRandomOrder()->get(),
            'testimonials' => Testimonial::get()->toArray(),
        ];

        $app_data['appData'] = collect($app_data);

        return view('welcome', $app_data);
    }
}
