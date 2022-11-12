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
<<<<<<< HEAD
              'location' => null,
=======
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Sisters Birthday',
              'details' => 'Buy a nice present',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'green',
<<<<<<< HEAD
              'icon' => 'fas fa-birthday-cake',
              'location' => 'Home',
=======
              'icon' => 'fas fa-birthday-cake'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Meeting',
              'details' => 'Time to pitch my idea to the company',
              'start_date' => now(),
              'duration' => 120,
              'bgcolor' => 'red',
<<<<<<< HEAD
              'icon' => 'fas fa-handshake',
              'location' => 'Zoom',
=======
              'icon' => 'fas fa-handshake'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Lunch',
              'details' => 'Company is paying!',
              'start_date' => now(),
              'duration' => 90,
              'bgcolor' => 'teal',
<<<<<<< HEAD
              'icon' => 'fas fa-hamburger',
              'location' => 'The local pub',
=======
              'icon' => 'fas fa-hamburger'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Visit mom',
              'details' => 'Always a nice chat with mom',
              'start_date' => now(),
              'duration' => 90,
              'bgcolor' => 'grey',
<<<<<<< HEAD
              'icon' => 'fas fa-car',
              'location' => 'Mom\'s house'
=======
              'icon' => 'fas fa-car'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Conference',
              'details' => 'Teaching Javascript 101',
              'start_date' => now(),
              'duration' => 540,
              'bgcolor' => 'blue',
<<<<<<< HEAD
              'icon' => 'fas fa-chalkboard-teacher',
              'location' => 'Amsterdam'
=======
              'icon' => 'fas fa-chalkboard-teacher'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Girlfriend',
              'details' => 'Meet GF for dinner at Swanky Restaurant',
              'start_date' => now(),
              'duration' => 180,
              'bgcolor' => 'teal',
<<<<<<< HEAD
              'icon' => 'fas fa-utensils',
              'location' => 'Swanky Restaurant',
=======
              'icon' => 'fas fa-utensils'
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Rowing',
              'details' => 'Stay in shape!',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'rowing',
<<<<<<< HEAD
              'location' => 'The River Thames',
=======
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Fishing',
              'details' => 'Time for some weekend R&R',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'fas fa-fish',
<<<<<<< HEAD
              'location' => 'Lake Ontario',
=======
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ],
            [
              'title' => 'Vacation',
              'details' => 'Trails and hikes, going camping! Don\'t forget to bring bear spray!',
              'start_date' => now(),
              'duration' => null,
              'bgcolor' => 'purple',
              'icon' => 'fas fa-plane',
<<<<<<< HEAD
              'location' => 'Banff, Alberta, Canada'
=======

>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            ]
        ];

        // Delete all events having meta->dummy = true
        Event::where('meta->dummy', true)->delete();
        // Free auto-incrementing id
<<<<<<< HEAD
        if (Event::max('id') > 0) {
=======
        if (Event::max('id')) {
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
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
<<<<<<< HEAD
                    'company_type' => Company::class,
                    'start_date' => $evtbl->due_date ?? now()->addDays(rand(1, 15)),
                    'user_id' => $company->user_id,
                    'slug' => str($event['title'])->slug(),
                    'location' => $evtbl->destination ?? $event['location'],
=======
                    'start_date' => now()->addDays(rand(1, 15)),
                    'user_id' => $company->user_id,
                    'slug' => str($event['title'])->slug(),
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
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
