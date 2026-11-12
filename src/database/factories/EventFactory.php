<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $eventName = 'EventNameHere ' . $this->faker->randomDigitNotNull();
        $startDate = $this->faker->dateTimeBetween('+1 months', '+2 months')->format('Y-m-d');
        $endDate   = date('Y-m-d', strtotime($startDate . ' + 2 days'));
        return [
            'display_name' => $eventName,
            // nice_name is unique, and there are only 9 names, so add a random part
            'nice_name'    => strtolower(str_replace(' ', '-', $eventName)) . '-' . Str::lower(Str::random(6)),
            'slug'         => strtolower(str_replace(' ', '-', $eventName)) . '-' . Str::random(4),
            'start'        => $startDate . ' 16:00:00',
            'end'          => $endDate . ' 18:00:00',
            'desc_long'    => $this->faker->paragraph(5),
            'desc_short'   => 'Some Awesome LAN Event',
            'status'       => 'PUBLISHED',
            'type'         => 'LAN',
        ];
    }
}
