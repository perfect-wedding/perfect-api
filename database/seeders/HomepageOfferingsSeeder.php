<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageOffering;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomepageOfferingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageOffering::truncate();
        HomepageOffering::insert([
            [
                'title' => 'Price',
                'subtitle' => 'Freemium',
                'slug' => 'freemium',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'icon' => 'fa-solid fa-check-circle',
                'features' => json_encode([
                    'We offer a fremium service, everyone signs up on our Platform at no cost to the users, service providers or warehouse vendors.',
                    'We charge a 6% commission on all completed transactions across the board regardless of what service is provided.',
                    'Service providers are charged a minimal onboarding fee for physical business verification.',
                ])
            ], [
                'title' => 'Security',
                'subtitle' => 'Escrow +',
                'slug' => 'escrow',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'icon' => 'fa-solid fa-check-circle',
                'features' => json_encode([
                    'Every single individual or company that participates on the perfectwedding.io platform is vetted through multiple processes to ensure safety.',
                    'With each transaction, money is held in escrow until service has been delivered to the user and their satisfaction expressed by rating the service provider.',
                    'We offer a full refund to clients after a thorough investigation in the event he/she is dissatisfied with a service.',
                ])
            ], [
                'title' => 'Carreer',
                'subtitle' => 'Work force',
                'slug' => 'work_force',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'icon' => 'fa-solid fa-check-circle',
                'features' => json_encode([
                    'We aim to promote commerce and create abundance of opportunity for it. Perfectwedding.io provides a concierge service which adopts the Gig workforce model, it allows individuals looking for extra sources of income or those interested in remote jobs to make more money on the side.',
                    'The concierges are tasked with physically verifying all businesses that sign up to the marketplace or warehouse to confirm legitimacy and competence thereby minimizing opportunity for fraud.',
                ])
            ],
        ]);
    }
}