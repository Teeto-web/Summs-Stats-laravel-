@extends('layouts.app')

@section('title', $game->queueLabel() . ' - Summs Stats')

@section('content')
    {{-- En-tête de la partie --}}
    <section class="card game-header">
        <div>
            <h1>{{ $game->queueLabel() }}</h1>
            <p class="muted">
                {{ $game->game_start_at->timezone('Europe/Paris')->format('d/m/Y à H:i') }}
                &middot; {{ $game->formattedDuration() }}
                &middot; Patch {{ $game->patch() }}
            </p>
        </div>

        <a href="{{ url()->previous() }}" class="button button-secondary">Retour</a>
    </section>

    {{-- Les deux équipes --}}
    @foreach ($teams as $team)
        <section class="card team team-{{ $team['color'] }}">
            <header class="team-header">
                <h2>
                    <span class="{{ $team['win'] ? 'positive' : 'negative' }}">
                        {{ $team['win'] ? 'Victoire' : 'Défaite' }}
                    </span>
                    &middot; {{ $team['name'] }}
                </h2>
                <p class="muted">
                    {{ $team['kills'] }} / {{ $team['deaths'] }} / {{ $team['assists'] }}
                    &middot; {{ number_format($team['gold'], 0, ',', ' ') }} or
                </p>
            </header>

            @foreach ($team['players'] as $participant)
                @include('games.partials.participant-row')
            @endforeach
        </section>
    @endforeach
@endsection
