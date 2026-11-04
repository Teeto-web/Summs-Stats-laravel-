{{-- Une ligne de l'historique : la partie vue par le joueur recherché --}}
@php
    $game = $participant->game;
    $minutes = max($game->game_duration / 60, 1);
@endphp

<article class="match {{ $participant->win ? 'match-win' : 'match-loss' }}">
    <div class="match-meta">
        <p class="match-queue">{{ $game->queueLabel() }}</p>
        <p class="muted">{{ $game->playedAgo() }}</p>
        <p class="match-result">{{ $participant->win ? 'Victoire' : 'Défaite' }}</p>
        <p class="muted">{{ $game->formattedDuration() }}</p>
    </div>

    <div class="match-champion">
        <img
            src="{{ $ddragon->championImageUrl($participant->champion_name) }}"
            alt="{{ $participant->champion_name }}"
            title="{{ $participant->champion_name }}"
            class="champion-icon"
            width="56"
            height="56"
            loading="lazy"
        >

        @include('partials.summoner-spells')
    </div>

    <div class="match-kda">
        <p class="kda-line">
            {{ $participant->kills }} / <span class="deaths">{{ $participant->deaths }}</span> / {{ $participant->assists }}
        </p>
        <p class="muted">{{ $participant->kda() }} KDA</p>
    </div>

    <div class="match-stats muted">
        <p>{{ $participant->cs }} CS ({{ round($participant->cs / $minutes, 1) }}/min)</p>
        <p>Vision {{ $participant->vision_score }}</p>
    </div>

    @include('partials.items')

    {{-- Lien invisible qui couvre toute la ligne : un clic n'importe où ouvre le détail --}}
    <a
        href="{{ route('games.show', ['game' => $game->riot_match_id, 'joueur' => $participant->puuid]) }}"
        class="match-link"
        aria-label="Voir le détail de la partie"
    ></a>
</article>
