<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\DB;

/**
 * Récupère les joueurs : depuis la base si les données sont récentes (cache),
 * sinon depuis l'API Riot, puis les enregistre en base.
 */
class PlayerService
{
    // Durée pendant laquelle les données en base sont considérées comme à jour
    private const CACHE_MINUTES = 10;

    // Délai minimum entre deux actualisations manuelles d'un même joueur
    private const REFRESH_COOLDOWN_MINUTES = 2;

    // Seules files classées affichées sur le site
    private const RANKED_QUEUES = ['RANKED_SOLO_5x5', 'RANKED_FLEX_SR'];

    public function __construct(private RiotApiService $riot)
    {
    }

    /**
     * Trouve un joueur par son Riot ID.
     * Renvoie null s'il n'existe pas ou n'a pas de compte LoL sur le serveur EUW.
     */
    public function find(string $gameName, string $tagLine): ?Player
    {
        $player = $this->findInDatabase($gameName, $tagLine);

        // Données récentes : on évite un appel à l'API
        if ($player !== null && $this->isFresh($player)) {
            return $player;
        }

        return $this->fetchFromApi($gameName, $tagLine);
    }

    /**
     * Force la mise à jour d'un joueur depuis l'API.
     * Renvoie false si la dernière mise à jour est trop récente.
     */
    public function refresh(Player $player): bool
    {
        if (! $this->canRefresh($player)) {
            return false;
        }

        $this->fetchFromApi($player->game_name, $player->tag_line);

        return true;
    }

    /**
     * Le bouton "Mettre à jour" est-il disponible ?
     */
    public function canRefresh(Player $player): bool
    {
        return $player->refreshed_at === null
            || $player->refreshed_at->lt(now()->subMinutes(self::REFRESH_COOLDOWN_MINUTES));
    }

    /**
     * Cherche le joueur en base. Les Riot ID ne tiennent pas compte des majuscules,
     * mais les accents comptent (Teeto et Teetô sont deux joueurs différents).
     * MySQL ignorant les accents dans ses comparaisons, on vérifie en PHP.
     */
    private function findInDatabase(string $gameName, string $tagLine): ?Player
    {
        return Player::where('game_name', $gameName)
            ->where('tag_line', $tagLine)
            ->get()
            ->first(function (Player $player) use ($gameName, $tagLine) {
                return mb_strtolower($player->game_name) === mb_strtolower($gameName)
                    && mb_strtolower($player->tag_line) === mb_strtolower($tagLine);
            });
    }

    private function isFresh(Player $player): bool
    {
        return $player->refreshed_at !== null
            && $player->refreshed_at->gt(now()->subMinutes(self::CACHE_MINUTES));
    }

    /**
     * Appelle l'API Riot (Account, Summoner, League) et enregistre le résultat en base.
     */
    private function fetchFromApi(string $gameName, string $tagLine): ?Player
    {
        $account = $this->riot->getAccountByRiotId($gameName, $tagLine);

        if ($account === null) {
            return null;
        }

        $summoner = $this->riot->getSummonerByPuuid($account['puuid']);

        // Le compte Riot existe mais pas de profil LoL sur EUW
        if ($summoner === null) {
            return null;
        }

        $entries = $this->riot->getRankedEntriesByPuuid($account['puuid']);

        // Transaction : soit tout est enregistré, soit rien (pas de joueur sans son classement)
        return DB::transaction(function () use ($account, $summoner, $entries) {
            // On cherche par puuid : si le joueur a changé de pseudo, on met à jour la même ligne
            $player = Player::updateOrCreate(
                ['puuid' => $account['puuid']],
                [
                    'game_name' => $account['gameName'],
                    'tag_line' => $account['tagLine'],
                    'profile_icon_id' => $summoner['profileIconId'],
                    'summoner_level' => $summoner['summonerLevel'],
                    'refreshed_at' => now(),
                ]
            );

            $queuesFound = [];

            foreach ($entries as $entry) {
                if (! in_array($entry['queueType'], self::RANKED_QUEUES)) {
                    continue;
                }

                $player->rankedEntries()->updateOrCreate(
                    ['queue_type' => $entry['queueType']],
                    [
                        'tier' => $entry['tier'],
                        'division' => $entry['rank'],
                        'league_points' => $entry['leaguePoints'],
                        'wins' => $entry['wins'],
                        'losses' => $entry['losses'],
                    ]
                );

                $queuesFound[] = $entry['queueType'];
            }

            // Supprime les classements qui n'existent plus (ex : début de saison)
            $player->rankedEntries()->whereNotIn('queue_type', $queuesFound)->delete();

            return $player;
        });
    }
}
