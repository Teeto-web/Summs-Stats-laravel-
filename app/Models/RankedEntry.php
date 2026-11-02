<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedEntry extends Model
{
    // Noms des rangs en français
    private const TIERS = [
        'IRON' => 'Fer',
        'BRONZE' => 'Bronze',
        'SILVER' => 'Argent',
        'GOLD' => 'Or',
        'PLATINUM' => 'Platine',
        'EMERALD' => 'Émeraude',
        'DIAMOND' => 'Diamant',
        'MASTER' => 'Maître',
        'GRANDMASTER' => 'Grand Maître',
        'CHALLENGER' => 'Challenger',
    ];

    // Ces rangs n'ont pas de division (pas de "Maître II")
    private const TIERS_WITHOUT_DIVISION = ['MASTER', 'GRANDMASTER', 'CHALLENGER'];

    protected $fillable = [
        'player_id',
        'queue_type',
        'tier',
        'division',
        'league_points',
        'wins',
        'losses',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Pourcentage de victoires en classé, arrondi à 1 décimale.
     */
    public function winrate(): float
    {
        $total = $this->wins + $this->losses;

        if ($total === 0) {
            return 0;
        }

        return round($this->wins / $total * 100, 1);
    }

    /**
     * Rang lisible, ex : "Platine III" ou "Maître"
     */
    public function rankLabel(): string
    {
        $label = self::TIERS[$this->tier] ?? ucfirst(strtolower($this->tier));

        if (in_array($this->tier, self::TIERS_WITHOUT_DIVISION)) {
            return $label;
        }

        return $label . ' ' . $this->division;
    }
}
