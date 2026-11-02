<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Summs Stats')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="logo">Summs<span>Stats</span></a>

            {{-- Petite barre de recherche, sauf sur l'accueil qui a déjà la grande --}}
            @unless (request()->routeIs('home'))
                @include('partials.search-form', ['size' => 'small'])
            @endunless
        </div>
    </header>

    <main class="container main">
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Erreur du formulaire de recherche, quelle que soit la page d'où il a été envoyé --}}
        @error('riot_id')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            {{-- Mention légale demandée par Riot Games pour les sites utilisant son API --}}
            <p>
                Summs Stats n'est pas approuvé par Riot Games et ne reflète pas les opinions de Riot Games
                ni de quiconque officiellement impliqué dans la production ou la gestion des propriétés de Riot Games.
                Riot Games et toutes les propriétés associées sont des marques commerciales ou des marques déposées de Riot Games, Inc.
            </p>
        </div>
    </footer>
</body>
</html>