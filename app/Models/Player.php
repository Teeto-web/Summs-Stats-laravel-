<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = [
        'puuid',
        'game_name',
        'tag_line',
        'profile_icon_id',
        'summoner_level',
        'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'refreshed_at' => 'datetime',
        ];
    }

    /**
     * Classements du joueur (SoloQ, Flex).
     */
    public function rankedEntries(): HasMany
    {
        return $this->hasMany(RankedEntry::class);
    }

    /**
     * Toutes les participations du joueur à des parties, reliées par le puuid.
     */
    public function participations(): HasMany
    {
        return $this->hasMany(GameParticipant::class, 'puuid', 'puuid');
    }

    /**
     * Riot ID complet, ex : Faker#KR1
     */
    public function riotId(): string
    {
        return $this->game_name . '#' . $this->tag_line;
    }
}
