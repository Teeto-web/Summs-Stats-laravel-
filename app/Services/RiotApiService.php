<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Regroupe tous les appels à l'API Riot Games.
 *
 * Deux types d'adresses :
 * - régionale (europe.api.riotgames.com) : Account-V1, Match-V5
 * - plateforme (euw1.api.riotgames.com)  : Summoner-V4, League-V4, Spectator-V5
 *
 * Chaque méthode renvoie les données sous forme de tableau PHP,
 * ou null si Riot répond 404 (joueur introuvable, pas de partie en cours...).
 */
class RiotApiService
{
    private string $key;
    private string $region;
    private string $platform;

    public function __construct()
    {
        $this->key = config('services.riot.key');
        $this->region = config('services.riot.region');
        $this->platform = config('services.riot.platform');
    }

    /* ---------------------------------------------------------------
     | ACCOUNT-V1 : Riot ID -> puuid
     * --------------------------------------------------------------- */

    /**
     * Retourne ['puuid' => ..., 'gameName' => ..., 'tagLine' => ...]
     */
    public function getAccountByRiotId(string $gameName, string $tagLine): ?array
    {
        return $this->get(
            $this->regionalUrl(),
            '/riot/account/v1/accounts/by-riot-id/' . rawurlencode($gameName) . '/' . rawurlencode($tagLine)
        );
    }

    /* ---------------------------------------------------------------
     | SUMMONER-V4 : icône et niveau
     * --------------------------------------------------------------- */

    /**
     * Retourne ['puuid' => ..., 'profileIconId' => ..., 'summonerLevel' => ...]
     */
    public function getSummonerByPuuid(string $puuid): ?array
    {
        return $this->get($this->platformUrl(), '/lol/summoner/v4/summoners/by-puuid/' . $puuid);
    }

    /* ---------------------------------------------------------------
     | LEAGUE-V4 : classement
     * --------------------------------------------------------------- */

    /**
     * Retourne une liste (une entrée par file classée jouée) :
     * [['queueType' => 'RANKED_SOLO_5x5', 'tier' => 'GOLD', 'rank' => 'II', 'leaguePoints' => 45, ...], ...]
     * Liste vide si le joueur n'est pas classé.
     */
    public function getRankedEntriesByPuuid(string $puuid): array
    {
        return $this->get($this->platformUrl(), '/lol/league/v4/entries/by-puuid/' . $puuid) ?? [];
    }

    /* ---------------------------------------------------------------
     | MATCH-V5 : parties
     * --------------------------------------------------------------- */

    /**
     * Retourne une liste d'identifiants de parties, de la plus récente à la plus ancienne.
     * Ex : ['EUW1_7123456789', 'EUW1_7123450000', ...]
     */
    public function getMatchIdsByPuuid(string $puuid, int $start = 0, int $count = 20): array
    {
        return $this->get(
            $this->regionalUrl(),
            '/lol/match/v5/matches/by-puuid/' . $puuid . '/ids',
            ['start' => $start, 'count' => $count]
        ) ?? [];
    }

    /**
     * Retourne le détail complet d'une partie (infos + les 10 participants).
     */
    public function getMatch(string $matchId): ?array
    {
        return $this->get($this->regionalUrl(), '/lol/match/v5/matches/' . $matchId);
    }

    /* ---------------------------------------------------------------
     | SPECTATOR-V5 : partie en cours
     * --------------------------------------------------------------- */

    /**
     * Retourne la partie en cours, ou null si le joueur n'est pas en jeu.
     */
    public function getActiveGame(string $puuid): ?array
    {
        return $this->get($this->platformUrl(), '/lol/spectator/v5/active-games/by-summoner/' . $puuid);
    }

    /* ---------------------------------------------------------------
     | Méthodes internes
     * --------------------------------------------------------------- */

    /**
     * Effectue un appel GET vers l'API Riot.
     *
     * - 404 : renvoie null (ce n'est pas une erreur, juste "rien trouvé")
     * - 429 : limite d'appels dépassée, on attend le délai indiqué par Riot puis on réessaie (2 fois max)
     * - autre erreur : lance une exception
     */
    private function get(string $baseUrl, string $path, array $query = []): ?array
    {
        try {
            $response = $this->client($baseUrl)->get($path, $query);
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return null;
            }

            throw $e;
        }

        return $response->json();
    }

    /**
     * Prépare un client HTTP avec la clé API et la gestion des limites.
     */
    private function client(string $baseUrl): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->withHeaders(['X-Riot-Token' => $this->key])
            ->acceptJson()
            ->timeout(10)
            ->retry(
                3,
                // Délai avant de réessayer : celui indiqué par Riot dans l'en-tête Retry-After (en secondes)
                function (int $attempt, \Exception $exception) {
                    $retryAfter = $exception instanceof RequestException
                        ? (int) $exception->response->header('Retry-After')
                        : 0;

                    return max($retryAfter, 1) * 1000;
                },
                // On ne réessaie que si Riot répond 429 (trop de requêtes)
                function (\Exception $exception) {
                    return $exception instanceof RequestException
                        && $exception->response->status() === 429;
                }
            );
    }

    private function regionalUrl(): string
    {
        return 'https://' . $this->region . '.api.riotgames.com';
    }

    private function platformUrl(): string
    {
        return 'https://' . $this->platform . '.api.riotgames.com';
    }
}
