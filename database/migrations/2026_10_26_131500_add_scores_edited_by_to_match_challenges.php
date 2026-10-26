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
            $table->foreignId('scores_edited_by')->nullable()->after('scores_saved_by')->constrained('users')->nullOnDelete();
        });

        DB::table('match_challenges')
            ->whereNotNull('scores_saved_by')
            ->update(['scores_edited_by' => DB::raw('scores_saved_by')]);
    }

    public function down(): void
    {
        Schema::table('match_challenges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scores_edited_by');
        });
    }
};
