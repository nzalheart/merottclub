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
        Schema::create('elo_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_list_id')->constrained('ranking_lists')->cascadeOnDelete();
            $table->foreignId('match_record_id')->constrained('match_records')->cascadeOnDelete();
            $table->foreignId('player_ranking_id')->constrained('player_rankings')->cascadeOnDelete();
            $table->integer('old_elo');
            $table->integer('new_elo');
            $table->integer('elo_change');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elo_histories');
    }
};
