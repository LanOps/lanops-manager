<?php

namespace Database\Factories;

use App\Models\EventInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventInformation>
 */
class EventInformationFactory extends Factory
{
    protected $model = EventInformation::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'text'  => $this->faker->paragraph(5),
        ];
    }
}
