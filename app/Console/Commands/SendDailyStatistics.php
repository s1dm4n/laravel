<?php

namespace App\Console\Commands;

use App\Mail\DailyStatisticsMail;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyStatistics extends Command
{
    protected $signature = 'statistics:send';

    protected $description = 'Send today statistics to moderators';

    public function handle(): int
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        $views = ArticleView::whereBetween('created_at', [$start, $end])->count();
        $comments = Comment::whereBetween('created_at', [$start, $end])->count();
        $emails = User::whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))->pluck('email');
        if ($extra = config('mail.moderator_address')) {
            $emails->push($extra);
        }
        foreach ($emails->filter()->unique() as $email) {
            Mail::to($email)->send(new DailyStatisticsMail($views, $comments, $start->toDateString()));
        }
        $this->info("Views: {$views}; comments: {$comments}. Statistics sent.");

        return self::SUCCESS;
    }
}
