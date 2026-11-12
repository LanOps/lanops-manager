<?php

namespace Database\Factories;

use App\Models\EventTimetable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTimetable>
 */
class EventTimetableFactory extends Factory
{
    protected $model = EventTimetable::class;

    public function definition(): array
    {
        return [
            'name'   => $this->faker->words(3, true),
            'status' => 'published',
        ];
    }
}
