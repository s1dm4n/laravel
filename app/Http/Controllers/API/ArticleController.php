<?php

namespace App\Http\Controllers\API;

use App\Events\NewArticleEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Jobs\VeryLongJob;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Article::orderByDesc('date')->orderByDesc('id')->paginate(6));
    }

    public function store(ArticleRequest $request)
    {
        $this->authorize('create', Article::class);

        $article = Article::create($request->validated());

        VeryLongJob::dispatch($article)->afterCommit();
        NewArticleEvent::dispatch($article);
        Notification::send(User::whereHas('role', fn ($query) => $query->where('slug', Role::READER))
            ->whereKeyNot($request->user()->id)->get(), new NewArticleNotification($article));
        for ($page = 1; $page <= (int) ceil(Article::count() / 6) + 1; $page++) {
            Cache::forget('articles.page.'.$page);
        }

        return response()->json($article, 201);
    }

    public function show(Article $article): JsonResponse
    {
        return response()->json($article->load('approvedComments.user:id,name'));
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);

        $article->update($request->validated());
        Cache::flush();

        return response()->json($article->fresh());
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $article->delete();
        Cache::flush();

        return response()->noContent();
    }
}
