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

        <div class="match-spells">
            @foreach ([$participant->summoner1_id, $participant->summoner2_id] as $spellId)
                @php($spellUrl = $ddragon->summonerSpellImageUrl($spellId))
                @if ($spellUrl)
                    <img src="{{ $spellUrl }}" alt="Sort d'invocateur" width="24" height="24" loading="lazy">
                @endif
            @endforeach
        </div>
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

    <div class="match-items">
        @for ($slot = 0; $slot <= 6; $slot++)
            @php($itemId = $participant->{'item' . $slot})
            @if ($itemId)
                <img src="{{ $ddragon->itemImageUrl($itemId) }}" alt="Objet {{ $itemId }}" width="28" height="28" loading="lazy">
            @else
                <span class="item-empty"></span>
            @endif
        @endfor
    </div>
</article>
