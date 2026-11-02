<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Data Dragon : fichiers statiques fournis par Riot (images des icônes, champions, objets...).
 * Les URL contiennent le numéro de version du jeu, qu'on récupère une fois par jour.
 */
class DataDragonService
{
    private const BASE_URL = 'https://ddragon.leagueoflegends.com';

    // Version utilisée seulement si Data Dragon ne répond pas
    private const FALLBACK_VERSION = '15.1.1';

    // Quelques champions dont le nom dans l'API Match diffère du nom de l'image
    private const CHAMPION_IMAGE_FIXES = [
        'FiddleSticks' => 'Fiddlesticks',
    ];

    // Gardés en mémoire pendant la requête pour ne pas relire le cache à chaque image
    private ?string $version = null;
    private ?array $summonerSpells = null;

    /**
     * Dernière version du jeu (ex : 16.21.1), gardée en cache 24 h.
     */
    public function latestVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        try {
            $this->version = Cache::remember('ddragon.version', now()->addDay(), function () {
                $versions = Http::timeout(5)->get(self::BASE_URL . '/api/versions.json')->throw()->json();

                return $versions[0];
            });
        } catch (\Throwable $e) {
            report($e);
            $this->version = self::FALLBACK_VERSION;
        }

        return $this->version;
    }

    /**
     * URL de l'icône de profil d'un joueur.
     */
    public function profileIconUrl(?int $iconId): string
    {
        // 29 = icône par défaut de League of Legends
        return $this->cdnUrl('profileicon/' . ($iconId ?? 29) . '.png');
    }

    /**
     * URL de l'image carrée d'un champion, à partir de son nom (ex : "Ahri").
     */
    public function championImageUrl(string $championName): string
    {
        $championName = self::CHAMPION_IMAGE_FIXES[$championName] ?? $championName;

        return $this->cdnUrl('champion/' . $championName . '.png');
    }

    /**
     * URL de l'image d'un objet.
     */
    public function itemImageUrl(int $itemId): string
    {
        return $this->cdnUrl('item/' . $itemId . '.png');
    }

    /**
     * URL de l'image d'un sort d'invocateur, ou null si inconnu.
     * L'API Match donne un numéro (4 = Flash), mais l'image porte un nom (SummonerFlash.png) :
     * on récupère la correspondance dans le fichier summoner.json de Data Dragon.
     */
    public function summonerSpellImageUrl(int $spellId): ?string
    {
        $image = $this->summonerSpells()[$spellId] ?? null;

        if ($image === null) {
            return null;
        }

        return $this->cdnUrl('spell/' . $image);
    }

    /**
     * Correspondance numéro du sort => nom de l'image, gardée en cache 24 h.
     */
    private function summonerSpells(): array
    {
        if ($this->summonerSpells !== null) {
            return $this->summonerSpells;
        }

        $version = $this->latestVersion();

        try {
            $this->summonerSpells = Cache::remember('ddragon.spells.' . $version, now()->addDay(), function () use ($version) {
                $json = Http::timeout(5)
                    ->get(self::BASE_URL . '/cdn/' . $version . '/data/en_US/summoner.json')
                    ->throw()
                    ->json();

                $spells = [];

                foreach ($json['data'] as $spell) {
                    $spells[(int) $spell['key']] = $spell['image']['full'];
                }

                return $spells;
            });
        } catch (\Throwable $e) {
            report($e);
            $this->summonerSpells = [];
        }

        return $this->summonerSpells;
    }

    private function cdnUrl(string $path): string
    {
        return self::BASE_URL . '/cdn/' . $this->latestVersion() . '/img/' . $path;
    }
}
