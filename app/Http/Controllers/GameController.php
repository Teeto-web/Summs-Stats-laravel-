<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\DataDragonService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GameController extends Controller
{
    // Ordre d'affichage des joueurs dans chaque équipe
    private const ROLE_ORDER = ['TOP', 'JUNGLE', 'MIDDLE', 'BOTTOM', 'UTILITY'];

    public function __construct(private DataDragonService $ddragon)
    {
    }

    /**
     * Page de détail d'une partie : les deux équipes et les stats des 10 joueurs.
     * Aucun appel à l'API : la partie est déjà en base.
     */
    public function show(Request $request, Game $game): View
    {
        $game->load('participants');

        // Sert à dessiner les barres de dégâts, relatives au joueur qui en a fait le plus
        $maxDamage = max(1, $game->participants->max('damage_to_champions'));

        return view('games.show', [
            'game' => $game,
            'teams' => $this->buildTeams($game->participants),
            'maxDamage' => $maxDamage,
            'ddragon' => $this->ddragon,
            // puuid du joueur recherché, pour surligner sa ligne
            'highlight' => $request->query('joueur'),
        ]);
    }

    /**
     * Regroupe les participants par équipe et calcule les totaux de chaque équipe.
     */
    private function buildTeams(Collection $participants): Collection
    {
        return $participants
            ->groupBy('team_id')
            ->sortKeys()
            ->map(function (Collection $players, int $teamId) {
                return [
                    'name' => match ($teamId) {
                        100 => 'Équipe bleue',
                        200 => 'Équipe rouge',
                        default => 'Équipe ' . $teamId,
                    },
                    'color' => $teamId === 200 ? 'red' : 'blue',
                    'win' => (bool) $players->first()->win,
                    'kills' => $players->sum('kills'),
                    'deaths' => $players->sum('deaths'),
                    'assists' => $players->sum('assists'),
                    'gold' => $players->sum('gold_earned'),
                    // Top, jungle, mid, ADC, support
                    'players' => $players->sortBy(fn ($player) => $this->roleIndex($player->team_position))->values(),
                ];
            });
    }

    /**
     * Position du rôle dans l'ordre d'affichage (les joueurs sans rôle à la fin).
     */
    private function roleIndex(?string $role): int
    {
        $index = array_search($role, self::ROLE_ORDER);

        return $index === false ? count(self::ROLE_ORDER) : $index;
    }
}
