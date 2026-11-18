<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Mail\DailyStatisticsMail;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CourseworkFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        Event::fake([NewArticleEvent::class]);
        Mail::fake();
    }

    public function test_new_article_notifies_readers_and_notification_can_only_be_read_by_owner(): void
    {
        $moderator = User::where('email', 'moderator@example.com')->firstOrFail();
        $reader = User::where('email', 'reader@example.com')->firstOrFail();
        $data = Article::factory()->make()->getAttributes();
        $this->actingAs($moderator)->post('/articles', $data)->assertRedirect();
        Event::assertDispatched(NewArticleEvent::class);
        $this->assertSame(0, $moderator->unreadNotifications()->count());
        $notice = $reader->unreadNotifications()->firstOrFail();

        $this->get('/notifications/'.$notice->id)->assertNotFound();
        $this->actingAs($reader)->get('/notifications/'.$notice->id)
            ->assertRedirect(route('articles.show', $notice->data['article_id']));
        $this->assertSame(0, $reader->unreadNotifications()->count());
    }

    public function test_cache_is_invalidated_after_creation_and_comment_moderation(): void
    {
        Cache::setDefaultDriver('database');
        $moderator = User::where('email', 'moderator@example.com')->firstOrFail();
        $article = Article::factory()->create();
        $this->get('/articles')->assertOk();
        $this->get('/articles/'.$article->id)->assertOk();
        $this->assertTrue(Cache::has('articles.page.1'));
        $this->assertTrue(Cache::has('articles.show.'.$article->id));
        $this->get('/articles')->assertOk();
        $this->get('/articles/'.$article->id)->assertOk()->assertSee($article->name);
        $this->actingAs($moderator)->post('/articles', Article::factory()->make()->getAttributes());
        $this->assertFalse(Cache::has('articles.page.1'));

        $comment = $article->comments()->create(['user_id' => $moderator->id, 'body' => 'Новый комментарий', 'approved' => false]);
        $this->patch('/comments/'.$comment->id.'/accept')->assertRedirect();
        $this->assertFalse(Cache::has('articles.show.'.$article->id));
        $this->get('/articles/'.$article->id)->assertSee('Новый комментарий');
    }

    public function test_statistics_count_today_only_and_track_successful_article_views(): void
    {
        $article = Article::factory()->create();
        $this->get('/articles/'.$article->id)->assertOk();
        $this->get('/articles/999999')->assertNotFound();
        $this->assertDatabaseCount('article_views', 1);
        ArticleView::factory()->create(['article_id' => $article->id, 'created_at' => now()->subDay()]);
        $user = User::firstOrFail();
        $article->comments()->create(['user_id' => $user->id, 'body' => 'Сегодня', 'approved' => false]);
        $yesterday = $article->comments()->create(['user_id' => $user->id, 'body' => 'Вчера', 'approved' => true]);
        $yesterday->forceFill(['created_at' => now()->subDay()])->save();
        $this->artisan('statistics:send')->assertSuccessful();
        Mail::assertSent(DailyStatisticsMail::class, fn ($mail) => $mail->views === 1 && $mail->comments === 1);
    }

    public function test_api_supports_token_authentication_crud_validation_and_role_checks(): void
    {
        $token = $this->postJson('/api/login', ['email' => 'moderator@example.com', 'password' => 'password'])
            ->assertOk()->json('token');
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->getJson('/api/articles')->assertOk()->assertJsonStructure(['data']);
        $data = Article::factory()->make()->getAttributes();
        $id = $this->postJson('/api/articles', $data, $headers)->assertCreated()->json('id');
        $this->getJson('/api/articles/'.$id)->assertOk()->assertJsonPath('name', $data['name']);
        $data['name'] = 'Обновлённая статья';
        $this->putJson('/api/articles/'.$id, $data, $headers)->assertOk()->assertJsonPath('name', $data['name']);
        $this->postJson('/api/articles', [], $headers)->assertUnprocessable();
        $this->deleteJson('/api/articles/'.$id, [], $headers)->assertNoContent();
        $this->getJson('/api/articles/'.$id)->assertNotFound();
    }

    public function test_api_rejects_guests_readers_and_invalid_credentials(): void
    {
        $this->postJson('/api/articles', [])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => 'reader@example.com', 'password' => 'wrong'])->assertUnprocessable();
        $reader = User::where('email', 'reader@example.com')->firstOrFail();
        $this->actingAs($reader, 'sanctum')->postJson('/api/articles', Article::factory()->make()->getAttributes())->assertForbidden();
    }
}
