<?php

namespace Tests\Feature;

use App\Models\MatchRecord;
use App\Models\MatchSet;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use App\Services\EloService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_win_and_loss_streaks_update_and_appear_on_leaderboard(): void
    {
        $list = RankingList::create([
            'title' => 'Streak Singles',
            'description' => 'Streak test list',
            'type' => 'singles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        [$a, $b, $c] = User::factory()->member()->count(3)->create();

        $ra = PlayerRanking::findOrCreateIndividual($list, $a->id);
        $rb = PlayerRanking::findOrCreateIndividual($list, $b->id);
        $rc = PlayerRanking::findOrCreateIndividual($list, $c->id);

        $elo = new EloService();

        // A beats B, then A beats C → A on +2
        $this->playMatch($elo, $list, $ra, $rb, winnerSide: 1);
        $this->playMatch($elo, $list, $ra->fresh(), $rc->fresh(), winnerSide: 1);

        $this->assertSame(2, $ra->fresh()->current_streak);
        $this->assertSame(-1, $rb->fresh()->current_streak);
        $this->assertSame(-1, $rc->fresh()->current_streak);

        // B beats A → A resets to -1, B to +1
        $this->playMatch($elo, $list, $ra->fresh(), $rb->fresh(), winnerSide: 2);

        $this->assertSame(-1, $ra->fresh()->current_streak);
        $this->assertSame(1, $rb->fresh()->current_streak);

        $response = $this->get(route('leaderboard', ['list_id' => $list->id]));
        $response->assertOk();
        $response->assertSee('Streak');
        $response->assertSee('+1');
        $response->assertSee('-1');
    }

    protected function playMatch(
        EloService $elo,
        RankingList $list,
        PlayerRanking $side1,
        PlayerRanking $side2,
        int $winnerSide,
    ): void {
        $match = MatchRecord::create([
            'ranking_list_id' => $list->id,
            'match_type' => 'singles',
            'side1_ranking_id' => $side1->id,
            'side2_ranking_id' => $side2->id,
            'status' => 'in_progress',
            'played_at' => now(),
        ]);

        MatchSet::create([
            'match_record_id' => $match->id,
            'set_number' => 1,
            'side1_score' => $winnerSide === 1 ? 11 : 5,
            'side2_score' => $winnerSide === 1 ? 5 : 11,
            'winner_side' => $winnerSide,
        ]);
        MatchSet::create([
            'match_record_id' => $match->id,
            'set_number' => 2,
            'side1_score' => $winnerSide === 1 ? 11 : 7,
            'side2_score' => $winnerSide === 1 ? 7 : 11,
            'winner_side' => $winnerSide,
        ]);

        $elo->processMatchResult(
            $match->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking'])
        );
    }
};
