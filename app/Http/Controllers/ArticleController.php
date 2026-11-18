<?php

namespace App\Http\Controllers;

use App\Events\NewArticleEvent;
use App\Http\Requests\ArticleRequest;
use App\Jobs\VeryLongJob;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ArticleController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Article::class);

        $page = max(1, (int) request('page', 1));
        $cached = Cache::remember('articles.page.'.$page, 300, fn () => [
            'items' => Article::orderByDesc('date')->orderByDesc('id')->forPage($page, 6)
                ->get()->map(fn ($item) => $item->getAttributes())->all(),
            'total' => Article::count(),
        ]);
        $articles = new LengthAwarePaginator(
            Article::hydrate($cached['items']), $cached['total'], 6, $page,
            ['path' => route('articles.index')],
        );

        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        $this->authorize('create', Article::class);

        return view('articles.create');
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

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Статья создана. Уведомление добавлено в очередь');
    }

    public function show(Article $article)
    {
        $this->authorize('view', $article);

        $cached = Cache::rememberForever('articles.show.'.$article->id, fn () => [
            'article' => $article->getAttributes(),
            'comments' => $article->approvedComments()->with('user:id,name')->get()
                ->map(fn ($comment) => ['attributes' => $comment->getAttributes(), 'user' => $comment->user?->getAttributes()])->all(),
        ]);
        $article = Article::hydrate([$cached['article']])->first();
        $comments = new Collection;
        foreach ($cached['comments'] as $item) {
            $comment = (new Comment)->newFromBuilder($item['attributes']);
            $comment->setRelation('user', $item['user'] ? (new User)->newFromBuilder($item['user']) : null);
            $comments->push($comment);
        }
        $article->setRelation('approvedComments', $comments);

        return view('articles.show', compact('article'));
    }

    public function edit(Article $article)
    {
        $this->authorize('update', $article);

        return view('articles.edit', compact('article'));
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);

        $article->update($request->validated());
        Cache::flush();

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Статья обновлена');
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $article->delete();
        Cache::flush();

        return redirect()
            ->route('articles.index')
            ->with('success', 'Статья удалена');
    }
}
