<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nepal national flag defaults: crimson red + blue on white.
     */
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color', 7)->default('#DC143C');
            $table->string('secondary_color', 7)->default('#003893');
            $table->timestamps();
        });

        DB::table('theme_settings')->insert([
            'primary_color' => '#DC143C',
            'secondary_color' => '#003893',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
