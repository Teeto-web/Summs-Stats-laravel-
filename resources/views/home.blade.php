@extends('layouts.app')

@section('content')
    <section class="hero">
        <h1>Summs<span>Stats</span></h1>
        <p class="hero-subtitle">Les statistiques League of Legends des joueurs du serveur EUW</p>

        @include('partials.search-form', ['size' => 'large'])

        @error('riot_id')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </section>
@endsection
