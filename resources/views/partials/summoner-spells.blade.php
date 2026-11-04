{{-- Les 2 sorts d'invocateur d'un participant --}}
<div class="spells">
    @foreach ([$participant->summoner1_id, $participant->summoner2_id] as $spellId)
        @php($spellUrl = $ddragon->summonerSpellImageUrl($spellId))
        @if ($spellUrl)
            <img src="{{ $spellUrl }}" alt="Sort d'invocateur" width="{{ $size ?? 24 }}" height="{{ $size ?? 24 }}" loading="lazy">
        @endif
    @endforeach
</div>
