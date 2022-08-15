<?php

namespace Database\Seeders;

use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Models\v1\Service;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Service::truncate();
        Service::insert([
            [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Inner beauty makeup artistry',
                'slug' => str('Inner beauty makeup artistry')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Click Photography',
                'slug' => str('Click Photography')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Click Photography is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Blossom cakes',
                'slug' => str('Blossom cakes')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Blossom cakes is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Laugh loud MC',
                'slug' => str('Laugh loud MC')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Laugh loud MC is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Dance to the beat – Disc Jockey',
                'slug' => str('Dance to the beat – Disc Jockey')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Dance to the beat – Disc Jockey is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Splendid wedding planners',
                'slug' => str('Splendid wedding planners')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Splendid wedding planners is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Inner beauty makeup artistry',
                'slug' => str('Inner beauty makeup artistry')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Plush Henna artist',
                'slug' => str('Plush Henna artist')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Plush Henna artist is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Inner beauty makeup artistry',
                'slug' => str('Inner beauty makeup artistry')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Exquisite decorators',
                'slug' => str('Exquisite decorators')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Exquisite decorators is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'The Light room',
                'slug' => str('The Light room')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('The Light room is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ], [
                'user_id' => ($company = Company::inRandomOrder()->first())->user_id,
                'category_id' => Category::inRandomOrder()->first()->id,
                'company_id' => $company->id,
                'title' => 'Blinks Asoebi place',
                'slug' => str('Blinks Asoebi place')->slug(),
                'basic_info' => 'Makeup for the bride or celebrant only',
                'short_desc' => \Str::words('Blinks Asoebi place is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.', 10, ''),
                'details' => 'Inner Beauty Make-Up Artistry is located in the heart of Abuja city, run by two sisters, Cynthia
                    and Mariyah Bussan, inner beauty is known for their exquisite make up, gorgeous hair and
                    fantastic customer relation.',
                'image' => random_img('images/bank/collect'),
                'price' => rand(50000, 200000),
            ],
        ]);
    }
}
