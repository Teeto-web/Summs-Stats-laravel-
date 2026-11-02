<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Joueurs recherchés sur le site.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();

            // Identifiant permanent du joueur chez Riot (ne change jamais, même si le pseudo change)
            $table->string('puuid', 100)->unique();

            // Riot ID : game_name#tag_line (ex : Faker#KR1)
            $table->string('game_name');
            $table->string('tag_line');

            $table->unsignedInteger('profile_icon_id')->nullable();
            $table->unsignedInteger('summoner_level')->nullable();

            // Date de la dernière récupération depuis l'API (sert au cache)
            $table->timestamp('refreshed_at')->nullable();

            $table->timestamps();

            // Accélère la recherche par Riot ID
            $table->index(['game_name', 'tag_line']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
