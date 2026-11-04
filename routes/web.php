<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

// Page d'accueil avec le formulaire de recherche
Route::view('/', 'home')->name('home');

// Détail d'une partie, ex : /partie/EUW1_8018961103
// {game:riot_match_id} : Laravel cherche directement la partie par cette colonne (sinon : erreur 404)
Route::get('/partie/{game:riot_match_id}', [GameController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('games.show');

// Liste des parties en JSON, appelée en JavaScript pendant leur téléchargement.
// Limite plus haute (120/min) car appelée toutes les 1,5 s, mais ne fait aucun appel à l'API Riot.
// Déclarée AVANT /joueur/{riotId}, sinon cette route l'intercepterait.
Route::get('/joueur/{player}/parties', [PlayerController::class, 'matches'])
    ->whereNumber('player')
    ->middleware('throttle:120,1')
    ->name('players.matches');

// "throttle:30,1" = 30 requêtes maximum par minute et par visiteur,
// pour éviter qu'un visiteur épuise les limites de l'API Riot
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/recherche', [PlayerController::class, 'search'])->name('players.search');

    Route::post('/joueur/{player}/actualiser', [PlayerController::class, 'refresh'])->name('players.refresh');

    // Ex : /joueur/Teetô-667
    Route::get('/joueur/{riotId}', [PlayerController::class, 'show'])
        ->where('riotId', '.+')
        ->name('players.show');
});
