<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameParticipant extends Model
{
    protected $fillable = [
        'game_id',
        'puuid',
        'riot_id_game_name',
        'riot_id_tagline',
        'champion_id',
        'champion_name',
        'team_id',
        'team_position',
        'win',
        'kills',
        'deaths',
        'assists',
        'cs',
        'gold_earned',
        'damage_to_champions',
        'vision_score',
        'item0',
        'item1',
        'item2',
        'item3',
        'item4',
        'item5',
        'item6',
        'summoner1_id',
        'summoner2_id',
    ];

    protected function casts(): array
    {
        return [
            'win' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Le joueur correspondant, s'il a déjà été recherché sur le site.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'puuid', 'puuid');
    }

    /**
     * KDA = (kills + assists) / morts. Si 0 mort, on renvoie kills + assists.
     */
    public function kda(): float
    {
        if ($this->deaths === 0) {
            return $this->kills + $this->assists;
        }

        return round(($this->kills + $this->assists) / $this->deaths, 2);
    }

    /**
     * Liste des objets sans les emplacements vides.
     */
    public function items(): array
    {
        $items = [
            $this->item0, $this->item1, $this->item2,
            $this->item3, $this->item4, $this->item5, $this->item6,
        ];

        return array_values(array_filter($items));
    }
}
