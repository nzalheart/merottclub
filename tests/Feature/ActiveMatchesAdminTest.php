<?php

namespace Tests\Feature;

use App\Filament\Resources\MatchChallengeResource\Pages\ListActiveMatches;
use App\Models\MatchChallenge;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActiveMatchesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_managers_see_open_matches_with_scores_and_acceptance_status(): void
    {
        $list = RankingList::create([
            'title' => 'Weekend Sprint',
            'description' => 'Singles',
            'type' => 'singles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);

        $manager = User::factory()->manager()->create();
        [$challenger, $opponent, $referee] = User::factory()->member()->count(3)->create([
            'name' => 'Player Alpha',
        ]);
        $challenger->update(['name' => 'Challenger One']);
        $opponent->update(['name' => 'Opponent Two']);
        $referee->update(['name' => 'Referee Three']);

        $this->actingAs($challenger)->post(route('matches.start.store'), [
            'ranking_list_id' => $list->id,
            'opponent_id' => $opponent->id,
            'referee_id' => $referee->id,
        ])->assertRedirect();

        $open = MatchChallenge::firstOrFail();

        $this->actingAs($challenger)->post(route('matches.challenge.scores', $open), [
            'sets' => [
                ['side1_score' => 11, 'side2_score' => 5],
                ['side1_score' => 11, 'side2_score' => 7],
            ],
            'intent' => 'save',
        ])->assertRedirect();

        $completed = MatchChallenge::create([
            'ranking_list_id' => $list->id,
            'match_type' => 'singles',
            'initiated_by' => $challenger->id,
            'referee_id' => $referee->id,
            'status' => MatchChallenge::STATUS_COMPLETED,
            'scores' => [
                ['side1_score' => 11, 'side2_score' => 3],
                ['side1_score' => 11, 'side2_score' => 4],
            ],
        ]);

        $this->actingAs($manager)
            ->get('/admin/active-matches')
            ->assertOk();

        Livewire::actingAs($manager)
            ->test(ListActiveMatches::class)
            ->assertCanSeeTableRecords([$open->fresh()])
            ->assertCanNotSeeTableRecords([$completed])
            ->assertSee('Challenger One')
            ->assertSee('Opponent Two')
            ->assertSee('Referee Three')
            ->assertSee('11–5')
            ->assertSee('11–7')
            ->assertSee('Accepted')
            ->assertSee('Pending')
            ->assertSee('Waiting for score acceptance');
    }

    public function test_members_cannot_open_active_matches_admin(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/admin/active-matches')
            ->assertForbidden();
    }
}
