<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_list_id')->constrained('ranking_lists')->cascadeOnDelete();
            $table->string('match_type');
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->json('scores')->nullable();
            $table->foreignId('match_record_id')->nullable()->constrained('match_records')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('match_challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_challenge_id')->constrained('match_challenges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->unsignedTinyInteger('side')->nullable();
            $table->timestamp('accepted_match_at')->nullable();
            $table->timestamp('accepted_score_at')->nullable();
            $table->timestamps();

            $table->unique(['match_challenge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_challenge_participants');
        Schema::dropIfExists('match_challenges');
    }
};
