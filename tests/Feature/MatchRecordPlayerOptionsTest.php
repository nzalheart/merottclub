<?php

namespace Tests\Feature;

use App\Filament\Resources\MatchRecordResource;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchRecordPlayerOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoreable_player_options_include_members_and_managers_but_not_admins(): void
    {
        $list = RankingList::create([
            'title' => 'Club Singles',
            'description' => 'Singles',
            'type' => 'singles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        $member = User::factory()->member()->create(['name' => 'Alice Member']);
        $manager = User::factory()->manager()->create(['name' => 'Bob Manager']);
        $admin = User::factory()->admin()->create(['name' => 'Carol Admin']);
        $inactive = User::factory()->member()->create([
            'name' => 'Dana Inactive',
            'status' => 'inactive',
        ]);

        // Only the admin was previously enrolled — members/managers should still appear.
        PlayerRanking::findOrCreateIndividual($list, $admin->id);

        $options = MatchRecordResource::scoreablePlayerOptions($list->id);

        $this->assertTrue($options->contains(fn ($label) => str_contains($label, 'Alice Member')));
        $this->assertTrue($options->contains(fn ($label) => str_contains($label, 'Bob Manager')));
        $this->assertFalse($options->contains(fn ($label) => str_contains($label, 'Carol Admin')));
        $this->assertFalse($options->contains(fn ($label) => str_contains($label, 'Dana Inactive')));

        $this->assertDatabaseHas('player_rankings', [
            'ranking_list_id' => $list->id,
            'user_id' => $member->id,
            'player2_id' => null,
        ]);
        $this->assertDatabaseHas('player_rankings', [
            'ranking_list_id' => $list->id,
            'user_id' => $manager->id,
            'player2_id' => null,
        ]);
    }

    public function test_scoreable_player_options_respect_exclusions(): void
    {
        $list = RankingList::create([
            'title' => 'Club Doubles',
            'description' => 'Doubles',
            'type' => 'doubles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        [$a, $b] = User::factory()->member()->count(2)->create();
        MatchRecordResource::ensureEligiblePlayersOnList($list);

        $rankingA = PlayerRanking::query()
            ->where('ranking_list_id', $list->id)
            ->where('user_id', $a->id)
            ->individuals()
            ->firstOrFail();

        $options = MatchRecordResource::scoreablePlayerOptions($list->id, [$rankingA->id]);

        $this->assertFalse($options->has($rankingA->id));
        $this->assertTrue(
            $options->keys()->contains(
                PlayerRanking::query()
                    ->where('ranking_list_id', $list->id)
                    ->where('user_id', $b->id)
                    ->individuals()
                    ->value('id')
            )
        );
    }
}
