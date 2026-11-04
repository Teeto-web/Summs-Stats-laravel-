{{-- Les 7 emplacements d'objets d'un participant (le 7e est la balise de vision) --}}
<div class="items">
    @for ($slot = 0; $slot <= 6; $slot++)
        @php($itemId = $participant->{'item' . $slot})
        @if ($itemId)
            <img src="{{ $ddragon->itemImageUrl($itemId) }}" alt="Objet {{ $itemId }}" width="28" height="28" loading="lazy">
        @else
            <span class="item-empty"></span>
        @endif
    @endfor
</div>
