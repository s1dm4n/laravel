<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleViewFactory extends Factory
{
    public function definition(): array
    {
        return ['article_id' => Article::factory(), 'url' => '/articles/1'];
    }
}
