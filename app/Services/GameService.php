<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Enregistre en base une partie renvoyée par l'API Match-V5.
 */
class GameService
{
    public function store(array $data): Game
    {
        $info = $data['info'];

        // Transaction : la partie et ses participants sont enregistrés ensemble, ou pas du tout
        return DB::transaction(function () use ($data, $info) {
            $game = Game::firstOrCreate(
                ['riot_match_id' => $data['metadata']['matchId']],
                [
                    'queue_id' => $info['queueId'],
                    'game_mode' => $info['gameMode'],
                    'game_version' => $info['gameVersion'],
                    'game_duration' => $info['gameDuration'],
                    // Riot donne la date en millisecondes depuis 1970
                    'game_start_at' => Carbon::createFromTimestampMs($info['gameStartTimestamp']),
                ]
            );

            // Partie déjà connue (téléchargée pour un autre joueur) : rien à ajouter
            if (! $game->wasRecentlyCreated) {
                return $game;
            }

            foreach ($info['participants'] as $participant) {
                $game->participants()->create([
                    'puuid' => $participant['puuid'],
                    'riot_id_game_name' => $participant['riotIdGameName'] ?? null,
                    'riot_id_tagline' => $participant['riotIdTagline'] ?? null,
                    'champion_id' => $participant['championId'],
                    'champion_name' => $participant['championName'],
                    'team_id' => $participant['teamId'],
                    // Vide en ARAM : on enregistre null plutôt qu'une chaîne vide
                    'team_position' => ($participant['teamPosition'] ?? '') ?: null,
                    'win' => $participant['win'],
                    'kills' => $participant['kills'],
                    'deaths' => $participant['deaths'],
                    'assists' => $participant['assists'],
                    // CS = sbires + monstres de la jungle
                    'cs' => ($participant['totalMinionsKilled'] ?? 0) + ($participant['neutralMinionsKilled'] ?? 0),
                    'gold_earned' => $participant['goldEarned'] ?? 0,
                    'damage_to_champions' => $participant['totalDamageDealtToChampions'] ?? 0,
                    'vision_score' => $participant['visionScore'] ?? 0,
                    'item0' => $participant['item0'] ?? 0,
                    'item1' => $participant['item1'] ?? 0,
                    'item2' => $participant['item2'] ?? 0,
                    'item3' => $participant['item3'] ?? 0,
                    'item4' => $participant['item4'] ?? 0,
                    'item5' => $participant['item5'] ?? 0,
                    'item6' => $participant['item6'] ?? 0,
                    'summoner1_id' => $participant['summoner1Id'] ?? 0,
                    'summoner2_id' => $participant['summoner2Id'] ?? 0,
                ]);
            }

            return $game;
        });
    }
}
