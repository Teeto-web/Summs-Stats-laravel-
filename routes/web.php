<?php

use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

// Page d'accueil avec le formulaire de recherche
Route::view('/', 'home')->name('home');

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
