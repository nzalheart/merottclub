<?php

namespace Tests\Feature;

use App\Models\MatchChallenge;
use App\Models\MatchChallengeParticipant;
use App\Models\MatchRecord;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankedMatchChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_does_not_see_start_match_and_cannot_open_the_form(): void
    {
        $list = $this->singlesList();

        $this->get(route('leaderboard', ['list_id' => $list->id]))
            ->assertOk()
            ->assertDontSee('Start match');

        $this->get(route('matches.start'))
            ->assertRedirect('/admin/login');
    }

    public function test_member_can_start_from_a_ranking_list_and_from_a_profile(): void
    {
        $list = $this->singlesList();
        $member = User::factory()->member()->create();
        $opponent = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($member)
            ->get(route('leaderboard', ['list_id' => $list->id]))
            ->assertOk()
            ->assertSee('Start match');

        $this->actingAs($member)
            ->get(route('player.profile', $opponent))
            ->assertOk()
            ->assertSee('Start match');

        $this->actingAs($member)
            ->get(route('player.profile', $admin))
            ->assertOk()
            ->assertDontSee('href="'.route('matches.start', ['opponent' => $admin->id]).'"', false);

        $this->actingAs($member)
            ->get(route('player.profile', $member))
            ->assertOk()
            ->assertDontSee('href="'.route('matches.start', ['opponent' => $member->id]).'"', false);
    }

    public function test_admins_cannot_be_challenged_but_can_referee(): void
    {
        $list = $this->singlesList();
        $challenger = User::factory()->member()->create();
        $opponent = User::factory()->member()->create();
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create(['name' => 'Admin Referee']);

        $players = User::query()->activePlayers()->orderBy('name')->pluck('id');
        $referees = User::query()->activeReferees()->orderBy('name')->pluck('id');

        $this->assertTrue($players->contains($opponent->id));
        $this->assertTrue($players->contains($manager->id));
        $this->assertFalse($players->contains($admin->id));
        $this->assertTrue($referees->contains($admin->id));

        $this->actingAs($challenger)
            ->get(route('matches.start', ['opponent' => $admin->id]))
            ->assertOk()
            ->assertSee($admin->name);

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $admin->id,
                'referee_id' => $manager->id,
            ])
            ->assertSessionHasErrors('match');

        $this->assertSame(0, MatchChallenge::count());

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $admin->id,
            ])
            ->assertRedirect();

        $challenge = MatchChallenge::first();
        $this->assertNotNull($challenge);
        $this->assertSame($admin->id, $challenge->referee_id);
    }

    public function test_singles_score_is_saved_only_after_all_three_accept(): void
    {
        $list = $this->singlesList();
        [$challenger, $opponent, $referee, $bystander] = User::factory()->member()->count(4)->create();

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $referee->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $challenge = MatchChallenge::first();
        $this->assertNotNull($challenge);
        $this->assertSame(MatchChallenge::STATUS_OPEN, $challenge->status);
        $this->assertNotNull($challenge->participants()->where('user_id', $challenger->id)->first()->accepted_match_at);
        $this->assertNull($challenge->participants()->where('user_id', $opponent->id)->first()->accepted_match_at);
        $this->assertSame(0, MatchRecord::count());
        $this->assertSame(0, PlayerRanking::count());

        $this->actingAs($challenger)
            ->get(route('matches.requests'))
            ->assertOk()
            ->assertSee($opponent->name)
            ->assertDontSee('No scores yet');

        $this->actingAs($opponent)
            ->get(route('leaderboard'))
            ->assertOk()
            ->assertSee('open match request');

        $this->actingAs($referee)
            ->get(route('leaderboard'))
            ->assertOk()
            ->assertSee('open match request');

        $this->actingAs($bystander)
            ->get(route('leaderboard'))
            ->assertOk()
            ->assertDontSee('open match request');

        $this->actingAs($opponent)
            ->get(route('matches.requests'))
            ->assertOk()
            ->assertSee($challenger->name)
            ->assertDontSee('No scores yet');

        $this->actingAs($opponent)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertDontSee('Score acceptance')
            ->assertSee($referee->name)
            ->assertSee(route('player.profile', $referee))
            ->assertSee('does not update live')
            ->assertSee('Cancel match')
            ->assertSee('Save scores')
            ->assertDontSee('Change scores')
            ->assertDontSee('Anyone can enter the scores')
            ->assertDontSee('Decline')
            ->assertDontSee('Accept score');

        $this->actingAs($bystander)
            ->post(route('matches.challenge.accept', $challenge))
            ->assertForbidden();

        $this->actingAs($opponent)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertSessionHasErrors('match');

        $this->actingAs($bystander)
            ->post(route('matches.challenge.scores', $challenge), [
                'sets' => [
                    ['side1_score' => 11, 'side2_score' => 10],
                ],
            ])
            ->assertSessionHasErrors('match');

        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($bystander)
            ->post(route('matches.challenge.scores', $challenge), [
                'sets' => [
                    ['side1_score' => 11, 'side2_score' => 5],
                ],
                'intent' => 'save',
            ])
            ->assertRedirect();

        $this->actingAs($bystander)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertSee('Change scores')
            ->assertDontSee('Save scores');

        $this->actingAs($opponent)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertSee('Change scores')
            ->assertDontSee('Save scores')
            ->assertSee('Score acceptance');

        $this->actingAs($opponent)
            ->get(route('matches.requests'))
            ->assertOk()
            ->assertSee($challenger->name)
            ->assertSee('11')
            ->assertSee('5')
            ->assertDontSee('Accept score');

        $this->actingAs($challenger)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertDontSee('Accept score');

        $this->actingAs($challenger)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertSessionHasErrors('match');
        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($bystander)
            ->post(route('matches.challenge.scores', $challenge), $this->sets())
            ->assertRedirect();

        $this->actingAs($challenger)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertSee('Accept score');

        $this->actingAs($challenger)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertRedirect();

        $this->assertNull(
            $challenge->participants()->where('user_id', $opponent->id)->first()->accepted_score_at
        );
        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($challenger)
            ->post(route('matches.challenge.scores', $challenge), [
                'sets' => [
                    ['side1_score' => 11, 'side2_score' => 3],
                    ['side1_score' => 11, 'side2_score' => 4],
                ],
                'intent' => 'save',
            ])
            ->assertRedirect();

        $this->assertSame($challenger->id, $challenge->fresh()->scores_edited_by);
        $this->assertNotNull(
            $challenge->participants()->where('user_id', $challenger->id)->first()->accepted_score_at
        );

        $this->actingAs($challenger)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertDontSee('Accept score');

        $this->actingAs($opponent)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertSee('Accept score');

        $this->actingAs($challenger)->post(route('matches.challenge.accept-score', $challenge))->assertRedirect();
        $this->actingAs($opponent)->post(route('matches.challenge.accept-score', $challenge))->assertRedirect();
        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($referee)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertRedirect()
            ->assertSessionHas('status');

        $challenge->refresh();
        $this->assertSame(MatchChallenge::STATUS_COMPLETED, $challenge->status);
        $this->assertSame(1, MatchRecord::count());

        $match = MatchRecord::first();
        $this->assertSame('completed', $match->status);
        $this->assertSame(1, $match->winner_side);
        $this->assertSame($referee->id, $match->manager_id);

        $winner = PlayerRanking::where('user_id', $challenger->id)->first();
        $loser = PlayerRanking::where('user_id', $opponent->id)->first();
        $this->assertGreaterThan(1200, $winner->elo_rating);
        $this->assertLessThan(1200, $loser->elo_rating);

        $this->get(route('matches'))
            ->assertOk()
            ->assertSee($challenger->name)
            ->assertSee($opponent->name);
    }

    public function test_referee_must_be_a_different_active_member_and_duplicates_are_blocked(): void
    {
        $list = $this->singlesList();
        $challenger = User::factory()->member()->create();
        $opponent = User::factory()->member()->create();
        $referee = User::factory()->member()->create();
        $pending = User::factory()->member()->create(['status' => 'pending']);

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $opponent->id,
            ])
            ->assertSessionHasErrors('match');

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $pending->id,
            ])
            ->assertSessionHasErrors('match');

        $this->assertSame(0, MatchChallenge::count());

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $referee->id,
            ])
            ->assertRedirect();

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $referee->id,
            ])
            ->assertSessionHasErrors('match');

        $this->assertSame(1, MatchChallenge::count());
    }

    public function test_decline_and_cancel_keep_the_score_out_of_the_rankings(): void
    {
        $list = $this->singlesList();
        [$challenger, $opponent, $referee] = User::factory()->member()->count(3)->create();

        $this->actingAs($challenger)->post(route('matches.start.store'), [
            'ranking_list_id' => $list->id,
            'opponent_id' => $opponent->id,
            'referee_id' => $referee->id,
        ]);

        $challenge = MatchChallenge::first();

        $this->actingAs($opponent)
            ->post(route('matches.challenge.decline', $challenge))
            ->assertRedirect();

        $this->assertSame(MatchChallenge::STATUS_DECLINED, $challenge->fresh()->status);
        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($challenger)->post(route('matches.start.store'), [
            'ranking_list_id' => $list->id,
            'opponent_id' => $opponent->id,
            'referee_id' => $referee->id,
        ])->assertRedirect();

        $second = MatchChallenge::query()->where('status', MatchChallenge::STATUS_OPEN)->first();

        $bystander = User::factory()->member()->create();

        $this->actingAs($bystander)
            ->post(route('matches.challenge.cancel', $second))
            ->assertRedirect();

        $this->assertSame(MatchChallenge::STATUS_CANCELLED, $second->fresh()->status);
        $this->assertSame(0, MatchRecord::count());
    }

    public function test_doubles_match_requires_every_player_and_the_referee(): void
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

        $players = User::factory()->member()->count(5)->create();
        [$challenger, $partner, $opponent, $opponentPartner, $referee] = $players;

        $this->actingAs($challenger)
            ->get(route('matches.start', ['list_id' => $list->id]))
            ->assertOk()
            ->assertSee('Your partner')
            ->assertSee('Referee');

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => $referee->id,
            ])
            ->assertSessionHasErrors('partner_id');

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'partner_id' => $partner->id,
                'opponent_id' => $opponent->id,
                'opponent_partner_id' => $opponentPartner->id,
                'referee_id' => $referee->id,
            ])
            ->assertRedirect();

        $challenge = MatchChallenge::first();
        $this->assertCount(5, $challenge->participants);

        $this->actingAs($referee)
            ->post(route('matches.challenge.scores', $challenge), $this->sets())
            ->assertRedirect();

        $this->assertNotNull(
            $challenge->participants()->where('user_id', $referee->id)->first()->accepted_score_at
        );

        foreach ([$challenger, $partner, $opponent] as $user) {
            $this->actingAs($user)
                ->post(route('matches.challenge.accept-score', $challenge))
                ->assertRedirect();
        }

        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($opponentPartner)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertRedirect();

        $this->assertSame(MatchChallenge::STATUS_COMPLETED, $challenge->fresh()->status);
        $this->assertSame(1, MatchRecord::count());
        $this->assertSame(4, PlayerRanking::query()->individuals()->count());
        $this->assertSame(2, PlayerRanking::query()->pairs()->count());
        $this->assertSame(
            5,
            MatchChallengeParticipant::where('match_challenge_id', $challenge->id)
                ->whereNotNull('accepted_score_at')
                ->count()
        );
    }

    public function test_match_can_be_started_and_recorded_without_a_referee(): void
    {
        $list = $this->singlesList();
        [$challenger, $opponent] = User::factory()->member()->count(2)->create();

        $this->actingAs($challenger)
            ->get(route('matches.start'))
            ->assertOk()
            ->assertSee('Without referee - Mutual consent')
            ->assertSee('A referee is optional');

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'opponent_id' => $opponent->id,
                'referee_id' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $challenge = MatchChallenge::first();
        $this->assertNotNull($challenge);
        $this->assertNull($challenge->referee_id);
        $this->assertCount(2, $challenge->participants);
        $this->assertNull(
            $challenge->participants()->where('role', MatchChallengeParticipant::ROLE_REFEREE)->first()
        );

        $this->actingAs($opponent)
            ->get(route('matches.challenge', $challenge))
            ->assertOk()
            ->assertSee('Without referee - Mutual consent')
            ->assertDontSee('Only the players and the referee');

        $this->actingAs($challenger)
            ->get(route('matches.requests'))
            ->assertOk()
            ->assertSee('Without referee - Mutual consent')
            ->assertDontSee('Accept the score')
            ->assertDontSee('Accept score');

        $this->actingAs($challenger)
            ->post(route('matches.challenge.scores', $challenge), $this->sets())
            ->assertRedirect();

        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($opponent)
            ->get(route('matches.requests'))
            ->assertOk()
            ->assertSee('Accept score')
            ->assertDontSee('Accept the score');

        $this->actingAs($opponent)
            ->post(route('matches.challenge.accept-score', $challenge), [
                'return_to' => 'requests',
            ])
            ->assertRedirect(route('matches.requests'))
            ->assertSessionHas('status');

        $challenge->refresh();
        $this->assertSame(MatchChallenge::STATUS_COMPLETED, $challenge->status);

        $match = MatchRecord::first();
        $this->assertNotNull($match);
        $this->assertNull($match->manager_id);
        $this->assertStringContainsString('Without referee - Mutual consent', (string) $match->notes);
        $this->assertSame('completed', $match->status);
    }

    public function test_doubles_match_can_be_recorded_without_a_referee(): void
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

        [$challenger, $partner, $opponent, $opponentPartner] = User::factory()->member()->count(4)->create();

        $this->actingAs($challenger)
            ->post(route('matches.start.store'), [
                'ranking_list_id' => $list->id,
                'partner_id' => $partner->id,
                'opponent_id' => $opponent->id,
                'opponent_partner_id' => $opponentPartner->id,
            ])
            ->assertRedirect();

        $challenge = MatchChallenge::first();
        $this->assertCount(4, $challenge->participants);
        $this->assertNull($challenge->referee_id);

        $this->actingAs($challenger)
            ->post(route('matches.challenge.scores', $challenge), $this->sets())
            ->assertRedirect();

        foreach ([$partner, $opponent] as $user) {
            $this->actingAs($user)
                ->post(route('matches.challenge.accept-score', $challenge))
                ->assertRedirect();
        }

        $this->assertSame(0, MatchRecord::count());

        $this->actingAs($opponentPartner)
            ->post(route('matches.challenge.accept-score', $challenge))
            ->assertRedirect();

        $this->assertSame(MatchChallenge::STATUS_COMPLETED, $challenge->fresh()->status);
        $this->assertNull(MatchRecord::first()?->manager_id);
    }

    protected function singlesList(): RankingList
    {
        return RankingList::create([
            'title' => 'Weekend Sprint',
            'description' => 'Singles',
            'type' => 'singles',
            'format' => 'best_of_3',
            'initial_elo' => 1200,
            'k_factor' => 32,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sets(): array
    {
        return [
            'sets' => [
                ['side1_score' => 11, 'side2_score' => 5],
                ['side1_score' => 11, 'side2_score' => 7],
            ],
            'intent' => 'save',
        ];
    }
}
