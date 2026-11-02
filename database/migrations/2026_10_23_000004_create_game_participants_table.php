<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les 10 joueurs d'une partie et leurs performances.
     */
    public function up(): void
    {
        Schema::create('game_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')->constrained()->cascadeOnDelete();

            // On garde le puuid plutôt qu'un lien vers players :
            // la plupart des participants n'ont jamais été recherchés sur le site
            $table->string('puuid', 100);
            $table->string('riot_id_game_name')->nullable();
            $table->string('riot_id_tagline')->nullable();

            $table->unsignedInteger('champion_id');
            $table->string('champion_name', 50);

            // 100 = équipe bleue, 200 = équipe rouge
            $table->unsignedSmallInteger('team_id');

            // TOP, JUNGLE, MIDDLE, BOTTOM, UTILITY (vide en ARAM)
            $table->string('team_position', 20)->nullable();

            $table->boolean('win');

            $table->unsignedSmallInteger('kills');
            $table->unsignedSmallInteger('deaths');
            $table->unsignedSmallInteger('assists');

            // Sbires + monstres neutres
            $table->unsignedInteger('cs');
            $table->unsignedInteger('gold_earned');
            $table->unsignedInteger('damage_to_champions');
            $table->unsignedSmallInteger('vision_score');

            // Objets (0 = emplacement vide), item6 = trinket
            $table->unsignedInteger('item0')->default(0);
            $table->unsignedInteger('item1')->default(0);
            $table->unsignedInteger('item2')->default(0);
            $table->unsignedInteger('item3')->default(0);
            $table->unsignedInteger('item4')->default(0);
            $table->unsignedInteger('item5')->default(0);
            $table->unsignedInteger('item6')->default(0);

            // Sorts d'invocateur (Flash, Ignite...)
            $table->unsignedSmallInteger('summoner1_id');
            $table->unsignedSmallInteger('summoner2_id');

            $table->timestamps();

            // Un joueur n'apparaît qu'une fois par partie
            $table->unique(['game_id', 'puuid']);

            // Accélère le calcul des stats d'un joueur (champions les plus joués...)
            $table->index(['puuid', 'champion_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_participants');
    }
};
