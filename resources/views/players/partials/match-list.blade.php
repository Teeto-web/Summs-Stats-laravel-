{{-- Contenu de l'historique, réutilisé par la page profil et par l'actualisation JavaScript --}}
@if ($loadingMatches)
    <p class="loading">Chargement des dernières parties…</p>
@endif

@forelse ($participations as $participant)
    @include('players.partials.match-row', ['participant' => $participant])
@empty
    @unless ($loadingMatches)
        <p class="muted">Aucune partie trouvée.</p>
    @endunless
@endforelse
