<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classement d'un joueur : une ligne par file classée (SoloQ et Flex).
     */
    public function up(): void
    {
        Schema::create('ranked_entries', function (Blueprint $table) {
            $table->id();

            // Si le joueur est supprimé, ses classements le sont aussi
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();

            // RANKED_SOLO_5x5 ou RANKED_FLEX_SR
            $table->string('queue_type');

            // Ex : GOLD
            $table->string('tier');

            // Ex : II. Riot l'appelle "rank", mais RANK est un mot réservé de MySQL 8
            $table->string('division', 5)->nullable();

            $table->unsignedInteger('league_points')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);

            $table->timestamps();

            // Un seul classement par file pour un même joueur
            $table->unique(['player_id', 'queue_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_entries');
    }
};
