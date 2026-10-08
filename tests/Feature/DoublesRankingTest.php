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

class DoublesRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_or_create_pair_uses_canonical_order_and_reuses_row(): void
    {
        $list = $this->makeDoublesList();
        [$a, $b] = User::factory()->member()->count(2)->create();

        $pair1 = PlayerRanking::findOrCreatePair($list, $a->id, $b->id);
        $pair2 = PlayerRanking::findOrCreatePair($list, $b->id, $a->id);

        $this->assertTrue($pair1->is($pair2));
        $this->assertSame(min($a->id, $b->id), $pair1->user_id);
        $this->assertSame(max($a->id, $b->id), $pair1->player2_id);
        $this->assertSame(1, PlayerRanking::where('ranking_list_id', $list->id)->pairs()->count());
        $this->assertSame(2, PlayerRanking::where('ranking_list_id', $list->id)->individuals()->count());
    }

    public function test_doubles_match_updates_four_individuals_and_two_pairs(): void
    {
        $list = $this->makeDoublesList();
        $players = User::factory()->member()->count(4)->create();

        foreach ($players as $player) {
            PlayerRanking::findOrCreateIndividual($list, $player->id);
        }

        $side1 = PlayerRanking::findOrCreatePair($list, $players[0]->id, $players[1]->id);
        $side2 = PlayerRanking::findOrCreatePair($list, $players[2]->id, $players[3]->id);

        $beforeIndividuals = PlayerRanking::where('ranking_list_id', $list->id)
            ->individuals()
            ->pluck('elo_rating', 'id');
        $beforePairs = [
            $side1->id => $side1->elo_rating,
            $side2->id => $side2->elo_rating,
        ];

        $match = MatchRecord::create([
            'ranking_list_id' => $list->id,
            'match_type' => 'doubles',
            'side1_ranking_id' => $side1->id,
            'side2_ranking_id' => $side2->id,
            'status' => 'in_progress',
            'played_at' => now(),
        ]);

        MatchSet::create([
            'match_record_id' => $match->id,
            'set_number' => 1,
            'side1_score' => 11,
            'side2_score' => 5,
            'winner_side' => 1,
        ]);
        MatchSet::create([
            'match_record_id' => $match->id,
            'set_number' => 2,
            'side1_score' => 11,
            'side2_score' => 7,
            'winner_side' => 1,
        ]);
        MatchSet::create([
            'match_record_id' => $match->id,
            'set_number' => 3,
            'side1_score' => 11,
            'side2_score' => 9,
            'winner_side' => 1,
        ]);

        (new EloService())->processMatchResult(
            $match->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking'])
        );

        $side1->refresh();
        $side2->refresh();

        $this->assertSame(1, $match->fresh()->winner_side);
        $this->assertGreaterThan($beforePairs[$side1->id], $side1->elo_rating);
        $this->assertLessThan($beforePairs[$side2->id], $side2->elo_rating);
        $this->assertSame(1, $side1->wins);
        $this->assertSame(1, $side2->losses);
        $this->assertSame(1, $side1->current_streak);
        $this->assertSame(-1, $side2->current_streak);

        $individuals = PlayerRanking::where('ranking_list_id', $list->id)->individuals()->get();
        $this->assertCount(4, $individuals);

        foreach ($individuals as $individual) {
            $this->assertSame(1, $individual->matches_played);
            $this->assertNotEquals(
                $beforeIndividuals[$individual->id],
                $individual->elo_rating,
                "Individual {$individual->id} ELO should change"
            );
        }

        $winners = $individuals->whereIn('user_id', [$players[0]->id, $players[1]->id]);
        $losers = $individuals->whereIn('user_id', [$players[2]->id, $players[3]->id]);

        $this->assertTrue($winners->every(fn (PlayerRanking $r) => $r->wins === 1 && $r->losses === 0));
        $this->assertTrue($losers->every(fn (PlayerRanking $r) => $r->wins === 0 && $r->losses === 1));

        // Both winners share the same team delta.
        $this->assertSame(
            $winners->first()->elo_rating - $beforeIndividuals[$winners->first()->id],
            $winners->last()->elo_rating - $beforeIndividuals[$winners->last()->id],
        );

        $this->assertSame(
            6,
            \App\Models\EloHistory::where('match_record_id', $match->id)->count()
        );
    }

    public function test_leaderboard_filters_individuals_and_pairs_for_doubles(): void
    {
        $list = $this->makeDoublesList();
        $players = User::factory()->member()->count(4)->create();

        foreach ($players as $player) {
            PlayerRanking::findOrCreateIndividual($list, $player->id);
        }

        PlayerRanking::findOrCreatePair($list, $players[0]->id, $players[1]->id);
        PlayerRanking::findOrCreatePair($list, $players[2]->id, $players[3]->id);

        $individualResponse = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'view' => 'individual',
        ]));
        $individualResponse->assertOk();
        $individualResponse->assertSee('Individual');
        $individualResponse->assertSee($players[0]->name);

        $pairResponse = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'view' => 'pair',
        ]));
        $pairResponse->assertOk();
        $pairResponse->assertSee('Pairs');

        $individualRankings = PlayerRanking::where('ranking_list_id', $list->id)->individuals()->get();
        $pairRankings = PlayerRanking::where('ranking_list_id', $list->id)->pairs()->get();

        $this->assertCount(4, $individualRankings);
        $this->assertCount(2, $pairRankings);
    }

    protected function makeDoublesList(): RankingList
    {
        return RankingList::create([
            'title' => 'Test Doubles',
            'description' => 'Doubles ranking test list',
            'type' => 'doubles',
            'format' => 'best_of_5',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);
    }
}
