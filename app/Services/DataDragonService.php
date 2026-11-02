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

    /**
     * Dernière version du jeu (ex : 16.21.1), gardée en cache 24 h.
     */
    public function latestVersion(): string
    {
        try {
            return Cache::remember('ddragon.version', now()->addDay(), function () {
                $versions = Http::timeout(5)->get(self::BASE_URL . '/api/versions.json')->throw()->json();

                return $versions[0];
            });
        } catch (\Throwable $e) {
            report($e);

            return self::FALLBACK_VERSION;
        }
    }

    /**
     * URL de l'icône de profil d'un joueur.
     */
    public function profileIconUrl(?int $iconId): string
    {
        // 29 = icône par défaut de League of Legends
        $iconId = $iconId ?? 29;

        return self::BASE_URL . '/cdn/' . $this->latestVersion() . '/img/profileicon/' . $iconId . '.png';
    }
}
