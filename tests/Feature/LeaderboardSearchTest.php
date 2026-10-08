<?php

namespace Tests\Feature;

use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_search_matches_name_and_nickname(): void
    {
        $list = RankingList::create([
            'title' => 'Search Singles',
            'description' => 'Search test list',
            'type' => 'singles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        $maLong = User::factory()->member()->create([
            'name' => 'Ma Long',
            'nickname' => 'dragon',
        ]);
        $fan = User::factory()->member()->create([
            'name' => 'Fan Zhendong',
            'nickname' => 'little_fat',
        ]);

        PlayerRanking::findOrCreateIndividual($list, $maLong->id);
        PlayerRanking::findOrCreateIndividual($list, $fan->id);

        $byFirstName = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'search' => 'Ma',
        ]));
        $byFirstName->assertOk();
        $byFirstName->assertSee('Ma Long');
        $byFirstName->assertDontSee('Fan Zhendong');

        $byLastName = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'search' => 'Zhendong',
        ]));
        $byLastName->assertOk();
        $byLastName->assertSee('Fan Zhendong');
        $byLastName->assertDontSee('Ma Long');

        $byNickname = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'search' => '@dragon',
        ]));
        $byNickname->assertOk();
        $byNickname->assertSee('Ma Long');
        $byNickname->assertDontSee('Fan Zhendong');
    }

    public function test_leaderboard_search_matches_either_player_in_pair_view(): void
    {
        $list = RankingList::create([
            'title' => 'Search Doubles',
            'description' => 'Doubles search test',
            'type' => 'doubles',
            'format' => 'best_of_5',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        $a = User::factory()->member()->create(['name' => 'Alice Wang', 'nickname' => 'ace']);
        $b = User::factory()->member()->create(['name' => 'Bob Chen', 'nickname' => 'bobby']);
        $c = User::factory()->member()->create(['name' => 'Cara Lee', 'nickname' => 'cara']);
        $d = User::factory()->member()->create(['name' => 'Dan Kim', 'nickname' => 'dank']);

        PlayerRanking::findOrCreatePair($list, $a->id, $b->id);
        PlayerRanking::findOrCreatePair($list, $c->id, $d->id);

        $response = $this->get(route('leaderboard', [
            'list_id' => $list->id,
            'view' => 'pair',
            'search' => 'bobby',
        ]));

        $response->assertOk();
        $response->assertSee('Alice Wang');
        $response->assertSee('Bob Chen');
        $response->assertDontSee('Cara Lee');
    }
}
