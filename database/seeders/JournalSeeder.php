<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $articles = json_decode(file_get_contents(public_path('articles.json')), true);
        foreach ($articles as $article) {
            Article::updateOrCreate(['name' => $article['name']], [
                'date' => '2026-11-18',
                'preview_image' => $article['preview_image'],
                'full_image' => $article['full_image'],
                'short_desc' => $article['shortDesc'],
                'desc' => $article['desc'],
            ]);
        }
    }
}
