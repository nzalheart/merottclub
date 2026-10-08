<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('player_rankings', function (Blueprint $table) {
            $table->integer('current_streak')->default(0)->after('sets_lost');
        });

        $this->backfillStreaks();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_rankings', function (Blueprint $table) {
            $table->dropColumn('current_streak');
        });
    }

    /**
     * Rebuild current_streak from completed match history (chronological).
     * Positive = consecutive wins, negative = consecutive losses.
     */
    private function backfillStreaks(): void
    {
        $rankings = DB::table('player_rankings')->get(['id', 'user_id', 'player2_id', 'ranking_list_id']);

        foreach ($rankings as $ranking) {
            $sideIds = [(int) $ranking->id];

            // Individual rows on doubles lists also accumulate results via pair matches.
            if ($ranking->player2_id === null) {
                $pairIds = DB::table('player_rankings')
                    ->where('ranking_list_id', $ranking->ranking_list_id)
                    ->whereNotNull('player2_id')
                    ->where(function ($q) use ($ranking) {
                        $q->where('user_id', $ranking->user_id)
                            ->orWhere('player2_id', $ranking->user_id);
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $sideIds = array_values(array_unique(array_merge($sideIds, $pairIds)));
            }

            $matches = DB::table('match_records')
                ->where('status', 'completed')
                ->where(function ($q) use ($sideIds) {
                    $q->whereIn('side1_ranking_id', $sideIds)
                        ->orWhereIn('side2_ranking_id', $sideIds);
                })
                ->orderBy('played_at')
                ->orderBy('id')
                ->get(['side1_ranking_id', 'side2_ranking_id', 'winner_side']);

            $streak = 0;

            foreach ($matches as $match) {
                $onSide1 = in_array((int) $match->side1_ranking_id, $sideIds, true);
                $won = $onSide1
                    ? (int) $match->winner_side === 1
                    : (int) $match->winner_side === 2;

                $streak = $won
                    ? ($streak > 0 ? $streak + 1 : 1)
                    : ($streak < 0 ? $streak - 1 : -1);
            }

            DB::table('player_rankings')
                ->where('id', $ranking->id)
                ->update(['current_streak' => $streak]);
        }
    }
};
