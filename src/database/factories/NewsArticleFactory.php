<?php

namespace Database\Factories;

use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsArticle>
 */
class NewsArticleFactory extends Factory
{
    protected $model = NewsArticle::class;

    public function definition(): array
    {
        return [
            'title'   => $this->faker->words(3, true),
            'article' => $this->faker->paragraph(5),
            'user_id' => User::factory(),
        ];
    }
}
