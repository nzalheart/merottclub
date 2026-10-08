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
        Schema::create('match_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_record_id')->constrained('match_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('set_number');
            $table->integer('side1_score')->default(0);
            $table->integer('side2_score')->default(0);
            $table->unsignedTinyInteger('winner_side')->nullable(); // 1 or 2
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_sets');
    }
};
