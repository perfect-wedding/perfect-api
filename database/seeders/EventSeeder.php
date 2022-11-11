<?php

namespace Database\Seeders;

use App\Models\v1\Company;
use App\Models\v1\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $events = [
            [
              'title' => '1st of the Month',
              'details' => 'Everything is funny as long as it is happening to someone else',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'orange',
              'icon' => null,
            ],
            [
              'title' => 'Sisters Birthday',
              'details' => 'Buy a nice present',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'green',
              'icon' => 'fas fa-birthday-cake'
            ],
            [
              'title' => 'Meeting',
              'details' => 'Time to pitch my idea to the company',
              'start_date' => now(),
              'duration' => 120,
              'bgcolor' => 'red',
              'icon' => 'fas fa-handshake'
            ],
            [
              'title' => 'Lunch',
              'details' => 'Company is paying!',
              'start_date' => now(),
              'duration' => 90,
              'bgcolor' => 'teal',
              'icon' => 'fas fa-hamburger'
            ],
            [
              'title' => 'Visit mom',
              'details' => 'Always a nice chat with mom',
              'start_date' => now(),
              'duration' => 90,
              'bgcolor' => 'grey',
              'icon' => 'fas fa-car'
            ],
            [
              'title' => 'Conference',
              'details' => 'Teaching Javascript 101',
              'start_date' => now(),
              'duration' => 540,
              'bgcolor' => 'blue',
              'icon' => 'fas fa-chalkboard-teacher'
            ],
            [
              'title' => 'Girlfriend',
              'details' => 'Meet GF for dinner at Swanky Restaurant',
              'start_date' => now(),
              'duration' => 180,
              'bgcolor' => 'teal',
              'icon' => 'fas fa-utensils'
            ],
            [
              'title' => 'Rowing',
              'details' => 'Stay in shape!',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'rowing',
            ],
            [
              'title' => 'Fishing',
              'details' => 'Time for some weekend R&R',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'fas fa-fish',
            ],
            [
              'title' => 'Vacation',
              'details' => 'Trails and hikes, going camping! Don\'t forget to bring bear spray!',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'fas fa-plane',

            ]
        ];

        // Delete all events having meta->dummy = true
        Event::where('meta->dummy', true)->delete();
        // Free auto-incrementing id
        if (Event::max('id')) {
            \DB::statement('ALTER TABLE events AUTO_INCREMENT = ?;', [Event::max('id') + 1]);
        } else {
            \DB::statement('ALTER TABLE events AUTO_INCREMENT = 1;');
        }

        Company::verified()->inRandomOrder()->get()->each(function ($company) use ($events) {
            // Create 5 events for each company
            $events = collect($events)->random(5)->map(function ($event) use ($company) {
                $serviceOrder = $company->orders()->inRandomOrder()->first();
                $serviceRequest = $company->orderRequests()->inRandomOrder()->first();
                $eventable = [
                    $serviceOrder,
                    $serviceRequest,
                ];

                $evtbl = $eventable[array_rand($eventable)];

                return array_merge($event, [
                    'title' => $evtbl->orderable->title ?? $evtbl->orderable->name ?? $event['title'],
                    'eventable' => $evtbl,
                    'company_id' => $company->id,
                    'start_date' => now()->addDays(rand(1, 15)),
                    'user_id' => $company->user_id,
                    'slug' => str($event['title'])->slug(),
                    'meta' => [
                        'created_by' => $company->user_id,
                        'updated_by' => $company->user_id,
                        'dummy' => true,
                    ]
                ]);
            });

            $events->filter(fn($e) => !!$e['eventable'])->each(function ($event) {
                $event['eventable']->events()->create(collect($event)->except('eventable')->toArray());
            });

            $events->filter(fn($e) => !$e['eventable'])->each(function ($event) use ($company) {
                $company->events()->create(collect($event)->except('eventable')->toArray());
            });
        });
    }
}
