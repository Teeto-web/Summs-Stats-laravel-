<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une partie de League of Legends.
 * Appelée "Game" car "Match" est un mot réservé en PHP 8.
 */
class Game extends Model
{
    // Noms des principaux modes de jeu, selon le queueId de Riot
    private const QUEUES = [
        400 => 'Normale (Draft)',
        420 => 'Classée Solo/Duo',
        430 => 'Normale (Aveugle)',
        440 => 'Classée Flex',
        450 => 'ARAM',
        490 => 'Partie rapide',
        700 => 'Clash',
        900 => 'ARURF',
        1700 => 'Arena',
        1900 => 'URF',
    ];

    protected $fillable = [
        'riot_match_id',
        'queue_id',
        'game_mode',
        'game_version',
        'game_duration',
        'game_start_at',
    ];

    protected function casts(): array
    {
        return [
            'game_start_at' => 'datetime',
        ];
    }

    /**
     * Les 10 joueurs de la partie.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(GameParticipant::class);
    }

    /**
     * Durée lisible, ex : 32:07
     */
    public function formattedDuration(): string
    {
        return sprintf('%d:%02d', intdiv($this->game_duration, 60), $this->game_duration % 60);
    }

    /**
     * Nom du mode de jeu, ex : "Classée Solo/Duo"
     */
    public function queueLabel(): string
    {
        return self::QUEUES[$this->queue_id] ?? ucfirst(strtolower($this->game_mode));
    }

    /**
     * Date relative en français, ex : "il y a 3 heures"
     */
    public function playedAgo(): string
    {
        return $this->game_start_at->locale('fr')->diffForHumans();
    }
}
