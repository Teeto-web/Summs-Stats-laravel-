{{-- Formulaire de recherche réutilisé sur l'accueil (grand) et dans l'en-tête (petit) --}}
<form action="{{ route('players.search') }}" method="GET" class="search-form search-form-{{ $size ?? 'large' }}">
    <input
        type="text"
        name="riot_id"
        value="{{ old('riot_id') }}"
        placeholder="Pseudo#TAG"
        aria-label="Riot ID"
        required
    >
    <button type="submit">Rechercher</button>
</form>
