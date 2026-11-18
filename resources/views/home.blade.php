@extends('layouts.app')

@section('title', 'Главная')

@section('content')
    <section class="editorial-hero">
        <div><span class="eyebrow">ЖУРНАЛ СТУДЕНЧЕСКИХ ИДЕЙ</span>
        <h1>Здесь начинается<br><em>что-то новое.</em></h1></div>
        <div class="hero-note"><span class="issue-number">01 / НА СТАРТЕ</span>
        <p>События университета, цифровые технологии и проекты, о которых хочется рассказать.</p>
        <a class="text-link" href="{{ route('articles.index') }}">Перейти к публикациям <span aria-hidden="true">↗</span></a></div>
    </section>
    <div class="section-heading"><h2>В фокусе</h2><span>Материалы редакции</span></div>

    <div class="news-grid">
        @foreach ($articles as $id => $article)
            <article class="news-card">
                <a href="{{ route('gallery', $id) }}">
                    <img src="{{ asset($article['preview_image']) }}" alt="{{ $article['name'] }}">
                </a>
                <div class="news-body">
                    <div class="story-meta"><span>ИДЕИ И ПРАКТИКА</span><time>{{ $article['date'] }}</time></div>
                    <h2><a href="{{ route('gallery', $id) }}">{{ $article['name'] }}</a></h2>
                    <p>{{ $article['shortDesc'] ?? \Illuminate\Support\Str::limit($article['desc'], 120) }}</p>
                </div>
            </article>
        @endforeach
    </div>
@endsection
