<?php

namespace App\Jobs;

use App\Mail\NewArticleMail;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class VeryLongJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Article $article) {}

    public function handle(): void
    {
        $emails = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))
            ->pluck('email');

        if ($notify = config('mail.moderator_address')) {
            $emails->push($notify);
        }

        foreach ($emails->unique()->filter() as $email) {
            Mail::to($email)->send(new NewArticleMail($this->article));
        }
    }
}
