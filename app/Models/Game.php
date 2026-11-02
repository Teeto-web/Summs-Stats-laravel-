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
}
