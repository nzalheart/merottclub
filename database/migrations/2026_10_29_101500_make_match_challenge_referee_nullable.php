<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_challenges', function (Blueprint $table) {
            $table->dropForeign(['referee_id']);
        });

        Schema::table('match_challenges', function (Blueprint $table) {
            $table->unsignedBigInteger('referee_id')->nullable()->change();
        });

        Schema::table('match_challenges', function (Blueprint $table) {
            $table->foreign('referee_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('match_challenges', function (Blueprint $table) {
            $table->dropForeign(['referee_id']);
        });

        Schema::table('match_challenges', function (Blueprint $table) {
            $table->unsignedBigInteger('referee_id')->nullable(false)->change();
        });

        Schema::table('match_challenges', function (Blueprint $table) {
            $table->foreign('referee_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
