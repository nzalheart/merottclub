<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_challenges', function (Blueprint $table) {
            $table->foreignId('scores_saved_by')->nullable()->after('scores')->constrained('users')->nullOnDelete();
        });

        DB::table('match_challenges')
            ->whereNotNull('scores')
            ->update(['scores_saved_by' => DB::raw('initiated_by')]);
    }

    public function down(): void
    {
        Schema::table('match_challenges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scores_saved_by');
        });
    }
};
