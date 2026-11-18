<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Jobs\VeryLongJob;
use App\Mail\NewArticleMail;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ArticleQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_notification_waits_in_database_until_worker_runs(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        config(['queue.default' => 'database']);
        Mail::fake();
        Event::fake([NewArticleEvent::class]);

        $moderator = User::where('email', 'moderator@example.com')->firstOrFail();
        $data = Article::factory()->make()->getAttributes();

        $this->actingAs($moderator)->post(route('articles.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('articles', ['name' => $data['name']]);
        $this->assertSame(1, DB::table('jobs')->count());
        Mail::assertNothingSent();

        Artisan::call('queue:work', ['--once' => true, '--tries' => 1]);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        Mail::assertSent(NewArticleMail::class, fn ($mail) => $mail->hasTo($moderator->email));
    }

    public function test_reader_cannot_create_an_article_or_queue_notification(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        config(['queue.default' => 'database']);
        $reader = User::where('email', 'reader@example.com')->firstOrFail();

        $this->actingAs($reader)->post(route('articles.store'), Article::factory()->make()->getAttributes())
            ->assertRedirect()
            ->assertSessionHas('error', 'Создавать статьи может только модератор.');

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_extra_recipient_is_not_notified_twice(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        config(['mail.moderator_address' => 'moderator@example.com']);
        Mail::fake();

        (new VeryLongJob(Article::factory()->create()))->handle();

        Mail::assertSent(NewArticleMail::class, 1);
    }
}
