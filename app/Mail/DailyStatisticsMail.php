<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DailyStatisticsMail extends Mailable
{
    public function __construct(public int $views, public int $comments, public string $date) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Статистика сайта за '.$this->date);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.daily-statistics');
    }
}
