<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class EventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        ## House Cleaning
        DB::table('events')->delete();
        DB::table('event_tickets')->delete();
        DB::table('event_timetables')->delete();
        DB::table('event_venues')->delete();
        DB::table('event_information')->delete();

        ## Venue
        $venue = \App\Models\EventVenue::factory()->create();
        foreach (['venue-1.jpg', 'venue-2.jpg', 'venue-3.jpg'] as $image) {
            $venue->addMedia(__DIR__ . '/images/' . $image)->preservingOriginal()->toMediaCollection();
        }

        ## Events
        \App\Models\Event::factory()->create([
            'event_venue_id'    => $venue->id,
            'status'            => 'PUBLISHED',
            'capacity'          => 30,
            'type'              => 'LAN',
            'desc_short'        => "Some Awesome LAN Event",
            'display_name'      => 'Another Awesome LAN Event',
            'nice_name'         => strtolower(str_replace(' ', '-', 'Another Awesome LAN Event')),
            'slug'              => strtolower(str_replace(' ', '-', 'Another Awesome LAN Event')),
        ])->each(
            function ($event) {
                \App\Models\EventTicket::factory()->create([
                    'event_id' => $event->id,
                ]);
                \App\Models\EventTimetable::factory()->create([
                    'event_id' => $event->id,
                ]);
                $information = \App\Models\EventInformation::factory()->count(5)->create([
                    'event_id' => $event->id,
                ]);
                $infoImages = ['info-a.jpg', 'info-b.jpg', 'info-c.jpg'];
                foreach ($information as $index => $section) {
                    if (isset($infoImages[$index])) {
                        $section->addMedia(__DIR__ . '/images/' . $infoImages[$index])->preservingOriginal()->toMediaCollection();
                    }
                }
                $seatingPlan = \App\Models\EventSeatingPlan::factory()->create([
                    'event_id' => $event->id,
                ]);
                $seatingPlan->addMedia(__DIR__ . '/images/seating-plan.jpg')->preservingOriginal()->toMediaCollection();
            }
        );

        \App\Models\Event::factory()->create([
            'event_venue_id'    => $venue->id,
            'status'            => 'PUBLISHED',
            'capacity'          => 30,
            'type'              => 'TABLETOP',
            'desc_short'        => "Some Awesome TABLETOP Event",
            'display_name'      => 'Another Awesome Tabletop Event',
            'nice_name'         => strtolower(str_replace(' ', '-', 'Another Awesome Tabletop Event')),
            'slug'              => strtolower(str_replace(' ', '-', 'Another Awesome Tabletop Event')),
        ])->each(
            function ($event) {
                \App\Models\EventTicket::factory()->create([
                    'event_id' => $event->id,
                ]);
                \App\Models\EventTimetable::factory()->create([
                    'event_id' => $event->id,
                ]);
                $information = \App\Models\EventInformation::factory()->count(5)->create([
                    'event_id' => $event->id,
                ]);
                $infoImages = ['info-a.jpg', 'info-b.jpg', 'info-c.jpg'];
                foreach ($information as $index => $section) {
                    if (isset($infoImages[$index])) {
                        $section->addMedia(__DIR__ . '/images/' . $infoImages[$index])->preservingOriginal()->toMediaCollection();
                    }
                }
                $seatingPlan = \App\Models\EventSeatingPlan::factory()->create([
                    'event_id' => $event->id,
                ]);
                $seatingPlan->addMedia(__DIR__ . '/images/seating-plan.jpg')->preservingOriginal()->toMediaCollection();
            }
        );
    }
}
