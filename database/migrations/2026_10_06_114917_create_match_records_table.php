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
        Schema::create('match_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_list_id')->constrained('ranking_lists')->cascadeOnDelete();
            $table->string('match_type')->default('singles'); // singles, doubles
            
            $table->foreignId('side1_ranking_id')->constrained('player_rankings')->cascadeOnDelete();
            $table->foreignId('side2_ranking_id')->constrained('player_rankings')->cascadeOnDelete();
            
            $table->unsignedTinyInteger('winner_side')->nullable(); // 1 or 2
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('completed'); // in_progress, completed, cancelled
            
            $table->integer('side1_sets_won')->default(0);
            $table->integer('side2_sets_won')->default(0);
            
            $table->integer('side1_elo_before')->nullable();
            $table->integer('side1_elo_after')->nullable();
            $table->integer('side2_elo_before')->nullable();
            $table->integer('side2_elo_after')->nullable();
            
            $table->timestamp('played_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_records');
    }
};
