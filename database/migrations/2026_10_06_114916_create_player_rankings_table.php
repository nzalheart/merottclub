<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_list_id')->constrained('ranking_lists')->cascadeOnDelete();
            // For Singles: user_id is the player
            // For Doubles: user_id is Partner 1, player2_id is Partner 2
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('team_name')->nullable();
            
            $table->integer('elo_rating')->default(1200);
            $table->integer('matches_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('sets_won')->default(0);
            $table->integer('sets_lost')->default(0);
            $table->timestamps();

            $table->unique(['ranking_list_id', 'user_id', 'player2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_rankings');
    }
};
