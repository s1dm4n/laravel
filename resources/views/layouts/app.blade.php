<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Сайт') — Серверная веб-разработка</title>
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('journal.css') }}?v=2">
</head>
<body>
    <div class="edition-bar"><div class="container"><span>УНИВЕРСИТЕТ · ТЕХНОЛОГИИ · ЛЮДИ</span><span>МОСКВА / {{ date('Y') }}</span></div></div>
    <header class="site-header">
        <div class="container">
            <a class="logo" href="{{ route('home') }}"><span class="logo-mark" aria-hidden="true">П.</span><span>Политех<span class="logo-subtitle">независимый взгляд на учёбу и технологии</span></span></a>
            <nav>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Главная</a>
                <a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'active' : '' }}">Публикации</a>
                @can('create', App\Models\Article::class)
                    <a href="{{ route('articles.create') }}" class="{{ request()->routeIs('articles.create') ? 'active' : '' }}">Создать новость</a>
                @endcan
                @can('moderate', App\Models\Comment::class)
                    <a href="{{ route('comments.moderate') }}" class="{{ request()->routeIs('comments.moderate') ? 'active' : '' }}">Модерация</a>
                @endcan
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">О нас</a>
                <a href="{{ route('contacts') }}" class="{{ request()->routeIs('contacts') ? 'active' : '' }}">Контакты</a>
                @auth
                    <details class="notifications">
                        <summary>Уведомления ({{ auth()->user()->unreadNotifications()->count() }})</summary>
                        <div class="notification-list">
                            @forelse (auth()->user()->unreadNotifications()->latest()->limit(20)->get() as $notice)
                                <a href="{{ route('notifications.show', $notice->id) }}">{{ $notice->data['name'] }}</a>
                            @empty
                                <p>Новых уведомлений нет</p>
                            @endforelse
                        </div>
                    </details>
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkish">Выход</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Вход</a>
                    <a href="{{ route('signin') }}" class="{{ request()->routeIs('signin') ? 'active' : '' }}">Регистрация</a>
                @endauth
            </nav>
        </div>
    </header>

    <div id="live-notifications"></div>
    <main>
        <div class="container">
            <div class="card">
                @if (session('success'))
                    <div class="flash">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="flash" style="background:#fdecea;color:#7f1d1d;border-color:#f5c2c7;">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <span class="footer-label">ПОЛИТЕХ / ЖУРНАЛ</span>
            {{ config('coursework.author') ?: 'Учебный проект по серверной веб-разработке' }}
            @if (config('coursework.group')) · {{ config('coursework.group') }} @endif
        </div>
    </footer>
</body>
</html>
