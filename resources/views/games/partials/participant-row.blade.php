{{-- Une ligne par joueur dans le détail d'une partie --}}
@php
    $minutes = max($game->game_duration / 60, 1);

    // Participation aux kills : part des kills de l'équipe auxquels le joueur a contribué
    $killParticipation = $team['kills'] > 0
        ? round(($participant->kills + $participant->assists) / $team['kills'] * 100)
        : 0;
@endphp

<div class="participant {{ $participant->puuid === $highlight ? 'participant-highlight' : '' }}">
    <div class="participant-champion">
        <img
            src="{{ $ddragon->championImageUrl($participant->champion_name) }}"
            alt="{{ $participant->champion_name }}"
            title="{{ $participant->champion_name }}"
            class="champion-icon"
            width="40"
            height="40"
            loading="lazy"
        >
        @include('partials.summoner-spells', ['size' => 18])
    </div>

    <div class="participant-name">
        @if ($participant->riot_id_game_name)
            <a href="{{ route('players.show', $participant->riot_id_game_name . '-' . $participant->riot_id_tagline) }}">
                {{ $participant->riot_id_game_name }}
            </a>
        @endif
        <p class="muted">{{ $participant->champion_name }}</p>
    </div>

    <div class="participant-kda">
        <p class="kda-line">
            {{ $participant->kills }} / <span class="deaths">{{ $participant->deaths }}</span> / {{ $participant->assists }}
        </p>
        <p class="muted">{{ $participant->kda() }} KDA &middot; {{ $killParticipation }} %</p>
    </div>

    <div class="participant-damage">
        <p>{{ number_format($participant->damage_to_champions, 0, ',', ' ') }}</p>
        <progress max="{{ $maxDamage }}" value="{{ $participant->damage_to_champions }}"></progress>
    </div>

    <div class="participant-stats muted">
        <p>{{ $participant->cs }} CS ({{ round($participant->cs / $minutes, 1) }})</p>
        <p>{{ number_format($participant->gold_earned, 0, ',', ' ') }} or &middot; Vision {{ $participant->vision_score }}</p>
    </div>

    @include('partials.items')
</div>
