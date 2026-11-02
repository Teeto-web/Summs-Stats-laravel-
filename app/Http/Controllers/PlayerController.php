<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\DataDragonService;
use App\Services\PlayerService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function __construct(
        private PlayerService $players,
        private DataDragonService $ddragon,
    ) {
    }

    /**
     * Formulaire de recherche : transforme "Pseudo#TAG" en URL /joueur/Pseudo-TAG
     */
    public function search(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['riot_id' => ['required', 'string', 'regex:/^[^#]{3,16}#[^#]{2,5}$/u']],
            [
                'riot_id.required' => 'Entre un Riot ID.',
                'riot_id.regex' => 'Le Riot ID doit être au format Pseudo#TAG.',
            ]
        );

        [$gameName, $tagLine] = $this->splitRiotId($validated['riot_id'], '#');

        return redirect()->route('players.show', trim($gameName) . '-' . trim($tagLine));
    }

    /**
     * Page profil d'un joueur
     */
    public function show(string $riotId): View|RedirectResponse
    {
        $parts = $this->splitRiotId($riotId, '-');

        if ($parts === null) {
            return redirect()->route('home')->with('error', 'Adresse de profil invalide.');
        }

        [$gameName, $tagLine] = $parts;

        try {
            $player = $this->players->find($gameName, $tagLine);
        } catch (RequestException | ConnectionException $e) {
            // Erreur côté Riot (clé expirée, serveur indisponible...) : on l'enregistre dans les logs
            report($e);

            return redirect()->route('home')->with('error', "L'API Riot ne répond pas pour le moment, réessaie dans quelques instants.");
        }

        if ($player === null) {
            return redirect()->route('home')
                ->with('error', 'Joueur ' . $gameName . '#' . $tagLine . ' introuvable sur le serveur EUW.');
        }

        $player->load('rankedEntries');

        return view('players.show', [
            'player' => $player,
            'iconUrl' => $this->ddragon->profileIconUrl($player->profile_icon_id),
            'soloQ' => $player->rankedEntries->firstWhere('queue_type', 'RANKED_SOLO_5x5'),
            'flex' => $player->rankedEntries->firstWhere('queue_type', 'RANKED_FLEX_SR'),
            'canRefresh' => $this->players->canRefresh($player),
        ]);
    }

    /**
     * Bouton "Mettre à jour"
     */
    public function refresh(Player $player): RedirectResponse
    {
        try {
            $refreshed = $this->players->refresh($player);
        } catch (RequestException | ConnectionException $e) {
            report($e);

            return back()->with('error', "L'API Riot ne répond pas pour le moment, réessaie dans quelques instants.");
        }

        if (! $refreshed) {
            return back()->with('error', 'Profil mis à jour il y a moins de 2 minutes, patiente un peu.');
        }

        // Le pseudo a pu changer : on recharge le joueur pour construire la bonne URL
        $player->refresh();

        return redirect()
            ->route('players.show', $player->game_name . '-' . $player->tag_line)
            ->with('success', 'Profil mis à jour.');
    }

    /**
     * Coupe un Riot ID au DERNIER séparateur.
     * Le tag ne contient jamais de "-" ou de "#", contrairement au pseudo parfois.
     * Renvoie [pseudo, tag] ou null si le format est invalide.
     */
    private function splitRiotId(string $value, string $separator): ?array
    {
        $position = mb_strrpos($value, $separator);

        if ($position === false) {
            return null;
        }

        $gameName = mb_substr($value, 0, $position);
        $tagLine = mb_substr($value, $position + 1);

        if ($gameName === '' || $tagLine === '') {
            return null;
        }

        return [$gameName, $tagLine];
    }
}
