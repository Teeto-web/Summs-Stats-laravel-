<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\GameService;
use App\Services\RiotApiService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

/**
 * Télécharge les dernières parties d'un joueur, en arrière-plan.
 *
 * Exécuté par le conteneur "queue" (php artisan queue:work) :
 * la page profil s'affiche tout de suite, sans attendre les appels à l'API.
 */
class FetchPlayerMatches implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    // Nombre de parties récupérées à chaque mise à jour
    public const MATCH_COUNT = 20;

    // Parties téléchargées en parallèle par paquet.
    // Riot autorise 20 requêtes par seconde : on envoie 10 requêtes, puis on attend
    // qu'une seconde se soit écoulée avant le paquet suivant.
    private const BATCH_SIZE = 10;

    // Un même joueur ne peut pas avoir deux téléchargements en cours (verrou de 10 min max)
    public int $uniqueFor = 600;

    public function __construct(public string $puuid)
    {
    }

    /**
     * Identifiant utilisé par ShouldBeUnique : un seul job par joueur.
     */
    public function uniqueId(): string
    {
        return $this->puuid;
    }

    /**
     * Le job peut être relancé pendant 15 min (en cas de limite d'API atteinte).
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }

    /**
     * Indique à la page profil qu'un téléchargement est en cours.
     */
    public static function markAsRunning(string $puuid): void
    {
        Cache::put(self::cacheKey($puuid), true, now()->addMinutes(10));
    }

    public static function isRunning(string $puuid): bool
    {
        return Cache::has(self::cacheKey($puuid));
    }

    public function handle(RiotApiService $riot, GameService $games): void
    {
        try {
            $matchIds = $riot->getMatchIdsByPuuid($this->puuid, 0, self::MATCH_COUNT);

            // On ne télécharge que les parties qui ne sont pas déjà en base
            $alreadyStored = Game::whereIn('riot_match_id', $matchIds)->pluck('riot_match_id')->all();

            $newMatchIds = array_values(array_diff($matchIds, $alreadyStored));

            foreach (array_chunk($newMatchIds, self::BATCH_SIZE) as $batch) {
                $startedAt = microtime(true);

                foreach ($riot->getMatches($batch) as $data) {
                    if ($data !== null) {
                        $games->store($data);
                    }
                }

                // Respecte la limite de 20 requêtes par seconde : au moins 1 s entre deux paquets
                $elapsed = microtime(true) - $startedAt;

                if ($elapsed < 1) {
                    usleep((int) ((1 - $elapsed) * 1_000_000));
                }
            }
        } catch (RequestException $e) {
            // Limite de l'API atteinte : on remet le job en attente du délai demandé par Riot.
            // Les parties déjà enregistrées ne seront pas re-téléchargées.
            if ($e->response->status() === 429) {
                $this->release((int) $e->response->header('Retry-After') ?: 60);

                return;
            }

            throw $e;
        }

        Cache::forget(self::cacheKey($this->puuid));
    }

    /**
     * Appelé si le job échoue définitivement.
     */
    public function failed(?\Throwable $exception): void
    {
        Cache::forget(self::cacheKey($this->puuid));
    }

    private static function cacheKey(string $puuid): string
    {
        return 'matches-loading.' . $puuid;
    }
}
