<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstname'      => $this->faker->firstName(),
            'surname'        => $this->faker->lastName(),
            'username'       => $this->faker->unique()->userName(),
            'email'          => $this->faker->unique()->safeEmail(),
            'password'       => bcrypt(Str::random(10)),
            'remember_token' => Str::random(10),
        ];
    }
}
