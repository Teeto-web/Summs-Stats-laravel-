<?php

namespace App\Console\Commands;

use App\Services\RiotApiService;
use Illuminate\Console\Command;

/**
 * Commande de test pour vérifier que l'API Riot répond.
 * Utilisation : php artisan riot:test "Pseudo#TAG"
 */
class RiotTestCommand extends Command
{
    protected $signature = 'riot:test {riotId : Le Riot ID, ex : Pseudo#EUW}';

    protected $description = "Teste la connexion à l'API Riot avec un Riot ID";

    public function handle(RiotApiService $riot): int
    {
        // On sépare "Pseudo#TAG" en deux parties
        $parts = explode('#', $this->argument('riotId'));

        if (count($parts) !== 2) {
            $this->error('Format attendu : Pseudo#TAG');
            return self::FAILURE;
        }

        [$gameName, $tagLine] = $parts;

        // 1. Account-V1
        $account = $riot->getAccountByRiotId($gameName, $tagLine);

        if ($account === null) {
            $this->error('Joueur introuvable.');
            return self::FAILURE;
        }

        $this->info('Compte trouvé : ' . $account['gameName'] . '#' . $account['tagLine']);
        $this->line('PUUID : ' . substr($account['puuid'], 0, 20) . '...');

        // 2. Summoner-V4
        $summoner = $riot->getSummonerByPuuid($account['puuid']);

        if ($summoner === null) {
            $this->warn("Pas de profil LoL sur ce serveur (le joueur est peut-être sur un autre serveur que EUW).");
            return self::SUCCESS;
        }

        $this->line('Niveau : ' . $summoner['summonerLevel'] . ' | Icône : ' . $summoner['profileIconId']);

        // 3. League-V4
        $entries = $riot->getRankedEntriesByPuuid($account['puuid']);

        if (count($entries) === 0) {
            $this->line('Classement : non classé');
        }

        foreach ($entries as $entry) {
            $this->line($entry['queueType'] . ' : ' . $entry['tier'] . ' ' . $entry['rank']
                . ' (' . $entry['leaguePoints'] . ' LP, ' . $entry['wins'] . 'V / ' . $entry['losses'] . 'D)');
        }

        // 4. Match-V5
        $matchIds = $riot->getMatchIdsByPuuid($account['puuid'], 0, 5);
        $this->line('5 dernières parties : ' . implode(', ', $matchIds));

        // 5. Spectator-V5
        $activeGame = $riot->getActiveGame($account['puuid']);
        $this->line('En partie : ' . ($activeGame ? 'oui' : 'non'));

        $this->info("L'API Riot fonctionne !");

        return self::SUCCESS;
    }
}
