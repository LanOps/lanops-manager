<?php

namespace Database\Factories;

use App\Models\EventVenue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventVenue>
 */
class EventVenueFactory extends Factory
{
    protected $model = EventVenue::class;

    public function definition(): array
    {
        $venueName = 'VenueNameHere ' . $this->faker->randomDigitNotNull();
        return [
            'display_name'     => $venueName,
            'slug'             => strtolower(str_replace(' ', '-', $venueName)) . '-' . Str::random(4),
            'address_1'        => $this->faker->secondaryAddress(),
            'address_street'   => $this->faker->streetName(),
            'address_city'     => $this->faker->city(),
            'address_postcode' => $this->faker->postcode(),
            'address_country'  => $this->faker->country(),
        ];
    }
}
