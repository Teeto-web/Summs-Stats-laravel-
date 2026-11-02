@extends('layouts.app')

@section('title', $player->riotId() . ' - Summs Stats')

@section('content')
    {{-- En-tête du profil --}}
    <section class="card profile">
        <div class="profile-icon">
            <img src="{{ $iconUrl }}" alt="Icône de profil" width="96" height="96">
            <span class="profile-level">{{ $player->summoner_level }}</span>
        </div>

        <div class="profile-info">
            <h1>
                {{ $player->game_name }}<span class="tag">#{{ $player->tag_line }}</span>
            </h1>
            <p class="muted">
                Mis à jour le {{ $player->refreshed_at->timezone('Europe/Paris')->format('d/m/Y à H:i') }}
            </p>

            <form action="{{ route('players.refresh', $player) }}" method="POST">
                @csrf
                <button type="submit" class="button" @disabled(! $canRefresh)>
                    Mettre à jour
                </button>
            </form>
        </div>
    </section>

    {{-- Classements --}}
    <section class="ranked">
        @foreach (['Classée Solo/Duo' => $soloQ, 'Classée Flex' => $flex] as $queueName => $entry)
            <div class="card ranked-card">
                <h2>{{ $queueName }}</h2>

                @if ($entry)
                    <p class="ranked-tier tier-{{ strtolower($entry->tier) }}">{{ $entry->rankLabel() }}</p>
                    <p>{{ $entry->league_points }} LP</p>
                    <p class="muted">
                        {{ $entry->wins }}V {{ $entry->losses }}D
                        &middot; {{ $entry->winrate() }} % de victoires
                    </p>
                @else
                    <p class="muted">Non classé</p>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Historique des parties --}}
    <section class="card">
        <h2>Historique des parties</h2>

        {{-- data-* : informations lues par le JavaScript (public/js/player.js) --}}
        <div
            id="match-list"
            data-loading="{{ $loadingMatches ? '1' : '0' }}"
            data-poll-url="{{ route('players.matches', $player) }}"
        >
            @include('players.partials.match-list')
        </div>
    </section>

    @if ($loadingMatches)
        {{-- Actualise la liste pendant le téléchargement, sans recharger la page --}}
        @push('scripts')
            <script src="{{ asset('js/player.js') }}" defer></script>
        @endpush

        {{-- Si JavaScript est désactivé : rechargement complet toutes les 5 secondes --}}
        @push('head')
            <noscript><meta http-equiv="refresh" content="5"></noscript>
        @endpush
    @endif
@endsection
