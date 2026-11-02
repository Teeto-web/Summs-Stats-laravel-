<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parties téléchargées depuis l'API Match-V5.
     * La table s'appelle "games" car "match" est un mot réservé en PHP 8.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            // Identifiant de la partie chez Riot (ex : EUW1_7123456789)
            $table->string('riot_match_id', 50)->unique();

            // Type de partie (420 = SoloQ, 440 = Flex, 450 = ARAM...)
            $table->unsignedInteger('queue_id');
            $table->string('game_mode', 50);

            // Version du jeu (patch), ex : 16.20.123.4567
            $table->string('game_version', 50);

            // Durée en secondes
            $table->unsignedInteger('game_duration');
            $table->timestamp('game_start_at');

            $table->timestamps();

            // Pour trier rapidement les parties par date
            $table->index('game_start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
