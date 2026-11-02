<?php

namespace App\Services;

use App\Jobs\FetchPlayerMatches;
use App\Models\Player;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les statistiques d'un joueur en SQL, à partir des parties enregistrées.
 * Aucun appel à l'API Riot : tout vient de la table game_participants.
 */
class PlayerStatsService
{
    // Les parties de moins de 5 minutes sont des "remakes" : on les ignore dans les stats
    private const MIN_GAME_DURATION = 300;

    // Nombre de champions affichés dans "Champions les plus joués"
    private const TOP_CHAMPIONS = 5;

    // Noms des rôles en français
    private const ROLES = [
        'TOP' => 'Haut',
        'JUNGLE' => 'Jungle',
        'MIDDLE' => 'Milieu',
        'BOTTOM' => 'Tireur',
        'UTILITY' => 'Support',
    ];

    /**
     * Renvoie ['summary' => ..., 'champions' => [...], 'roles' => [...]],
     * ou null si le joueur n'a aucune partie enregistrée.
     */
    public function forPlayer(Player $player): ?array
    {
        $summary = $this->summary($player->puuid);

        if ($summary === null) {
            return null;
        }

        return [
            'summary' => $summary,
            'champions' => $this->topChampions($player->puuid),
            'roles' => $this->roles($player->puuid),
        ];
    }

    /**
     * Les dernières parties du joueur, sous forme de sous-requête.
     *
     * SQL équivalent :
     *   SELECT gp.*, g.game_duration
     *   FROM game_participants gp
     *   JOIN games g ON g.id = gp.game_id
     *   WHERE gp.puuid = ? AND g.game_duration >= 300
     *   ORDER BY g.game_start_at DESC
     *   LIMIT 20
     *
     * Les calculs suivants se font sur ce résultat : FROM (cette requête) AS recent
     * (MySQL n'accepte pas de LIMIT dans un "WHERE id IN (...)", d'où la sous-requête dans le FROM)
     */
    private function recentGames(string $puuid): Builder
    {
        $recent = DB::table('game_participants as gp')
            ->join('games as g', 'g.id', '=', 'gp.game_id')
            ->where('gp.puuid', $puuid)
            ->where('g.game_duration', '>=', self::MIN_GAME_DURATION)
            ->orderByDesc('g.game_start_at')
            ->limit(FetchPlayerMatches::MATCH_COUNT)
            ->select('gp.*', 'g.game_duration');

        return DB::query()->fromSub($recent, 'recent');
    }

    /**
     * Résumé global : winrate, KDA, CS par minute, vision, dégâts.
     */
    private function summary(string $puuid): ?array
    {
        $row = $this->recentGames($puuid)
            ->selectRaw('
                COUNT(*) AS games,
                SUM(win) AS wins,
                AVG(kills) AS avg_kills,
                AVG(deaths) AS avg_deaths,
                AVG(assists) AS avg_assists,
                SUM(kills) AS total_kills,
                SUM(deaths) AS total_deaths,
                SUM(assists) AS total_assists,
                SUM(cs) AS total_cs,
                SUM(game_duration) AS total_seconds,
                AVG(vision_score) AS avg_vision,
                AVG(damage_to_champions) AS avg_damage
            ')
            ->first();

        if ($row === null || (int) $row->games === 0) {
            return null;
        }

        $games = (int) $row->games;
        $wins = (int) $row->wins;

        return [
            'games' => $games,
            'wins' => $wins,
            'losses' => $games - $wins,
            'winrate' => round($wins / $games * 100),
            'avg_kills' => round($row->avg_kills, 1),
            'avg_deaths' => round($row->avg_deaths, 1),
            'avg_assists' => round($row->avg_assists, 1),
            // KDA calculé sur les totaux, plus juste qu'une moyenne de KDA
            'kda' => $this->kda($row->total_kills, $row->total_deaths, $row->total_assists),
            'cs_per_minute' => round($row->total_cs / ($row->total_seconds / 60), 1),
            'avg_vision' => round($row->avg_vision),
            'avg_damage' => (int) round($row->avg_damage),
        ];
    }

    /**
     * Champions les plus joués.
     *
     * SQL équivalent :
     *   SELECT champion_name, COUNT(*) AS games, SUM(win) AS wins, SUM(kills)...
     *   FROM (...) AS recent
     *   GROUP BY champion_name
     *   ORDER BY games DESC, wins DESC
     *   LIMIT 5
     */
    private function topChampions(string $puuid): array
    {
        $rows = $this->recentGames($puuid)
            ->selectRaw('
                champion_name,
                COUNT(*) AS games,
                SUM(win) AS wins,
                SUM(kills) AS kills,
                SUM(deaths) AS deaths,
                SUM(assists) AS assists
            ')
            ->groupBy('champion_name')
            ->orderByDesc('games')
            ->orderByDesc('wins')
            ->limit(self::TOP_CHAMPIONS)
            ->get();

        $champions = [];

        foreach ($rows as $row) {
            $games = (int) $row->games;

            $champions[] = [
                'name' => $row->champion_name,
                'games' => $games,
                'winrate' => round($row->wins / $games * 100),
                'kda' => $this->kda($row->kills, $row->deaths, $row->assists),
            ];
        }

        return $champions;
    }

    /**
     * Répartition des rôles joués, en pourcentage.
     * Les parties sans rôle (ARAM, Arena...) sont ignorées.
     */
    private function roles(string $puuid): array
    {
        $rows = $this->recentGames($puuid)
            ->selectRaw('team_position, COUNT(*) AS games')
            ->whereNotNull('team_position')
            ->groupBy('team_position')
            ->orderByDesc('games')
            ->get();

        $total = $rows->sum('games');
        $roles = [];

        foreach ($rows as $row) {
            $roles[] = [
                'name' => self::ROLES[$row->team_position] ?? $row->team_position,
                'games' => (int) $row->games,
                'percent' => (int) round($row->games / $total * 100),
            ];
        }

        return $roles;
    }

    /**
     * KDA = (kills + assists) / morts. Sans aucune mort, on renvoie kills + assists.
     */
    private function kda(int|string $kills, int|string $deaths, int|string $assists): float
    {
        if ((int) $deaths === 0) {
            return (float) ($kills + $assists);
        }

        return round(($kills + $assists) / $deaths, 2);
    }
}
