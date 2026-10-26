<?php

namespace App\Services;

use App\Models\MatchChallenge;
use App\Models\MatchChallengeParticipant;
use App\Models\MatchRecord;
use App\Models\MatchSet;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RankedMatchService
{
    public function start(
        User $challenger,
        RankingList $list,
        int $opponentId,
        int $refereeId,
        ?int $partnerId = null,
        ?int $opponentPartnerId = null,
    ): MatchChallenge {
        return DB::transaction(function () use ($challenger, $list, $opponentId, $refereeId, $partnerId, $opponentPartnerId) {
            if (! $list->is_active) {
                throw new InvalidArgumentException('Choose an active ranking list.');
            }

            $challenger = $this->activeMember($challenger->id, 'You need an active membership to start a match.');
            $opponent = $this->activeMember($opponentId, 'Choose an active member as the opponent.');
            $referee = $this->activeMember($refereeId, 'Choose an active member as the referee.');

            $rows = [
                $this->participantRow($challenger->id, MatchChallengeParticipant::ROLE_CHALLENGER, 1, true),
                $this->participantRow($opponent->id, MatchChallengeParticipant::ROLE_OPPONENT, 2, false),
            ];

            if ($list->type === 'doubles') {
                if (! $partnerId || ! $opponentPartnerId) {
                    throw new InvalidArgumentException('A doubles match needs your partner, two opponents, and a referee.');
                }

                $partner = $this->activeMember($partnerId, 'Choose an active member as your partner.');
                $opponentPartner = $this->activeMember($opponentPartnerId, 'Choose an active member as the second opponent.');

                $rows[] = $this->participantRow($partner->id, MatchChallengeParticipant::ROLE_PARTNER, 1, false);
                $rows[] = $this->participantRow($opponentPartner->id, MatchChallengeParticipant::ROLE_OPPONENT_PARTNER, 2, false);
            }

            $rows[] = $this->participantRow($referee->id, MatchChallengeParticipant::ROLE_REFEREE, null, false);

            $ids = collect($rows)->pluck('user_id');
            if ($ids->unique()->count() !== $ids->count()) {
                throw new InvalidArgumentException('The players and the referee must all be different members.');
            }

            $this->guardAgainstDuplicate($list, collect($rows)
                ->whereNotNull('side')
                ->pluck('user_id')
                ->all());

            $challenge = MatchChallenge::create([
                'ranking_list_id' => $list->id,
                'match_type' => $list->type,
                'initiated_by' => $challenger->id,
                'referee_id' => $referee->id,
                'status' => MatchChallenge::STATUS_OPEN,
            ]);

            $challenge->participants()->createMany($rows);

            return $challenge->fresh(['participants.user', 'rankingList', 'referee']);
        });
    }

    /**
     * @param  array<int, array{side1_score?: mixed, side2_score?: mixed}>  $sets
     */
    public function saveScores(MatchChallenge $challenge, array $sets, User $user): MatchChallenge
    {
        return DB::transaction(function () use ($challenge, $sets, $user) {
            $locked = $this->lock($challenge);
            $this->ensureOpen($locked);

            $parsed = $this->parseSets($locked, $sets);
            $changed = $parsed !== array_values($locked->scores ?? []);

            $locked->scores = $parsed;
            if ($locked->scores_saved_by === null) {
                $locked->scores_saved_by = $user->id;
            }
            $locked->scores_edited_by = $user->id;
            $locked->save();

            if ($changed) {
                $locked->participants()->update(['accepted_score_at' => null]);
            }

            $locked->setRelation('participants', $locked->participants()->with('user')->get());

            if ($locked->hasValidScores()) {
                $saver = $locked->participants->firstWhere('user_id', $user->id);

                if ($saver && $saver->accepted_score_at === null) {
                    $saver->update(['accepted_score_at' => now()]);
                    $locked->setRelation('participants', $locked->participants()->with('user')->get());
                }
            }

            return $this->finalizeIfReady($locked);
        });
    }

    public function acceptMatch(MatchChallenge $challenge, User $user): MatchChallenge
    {
        return DB::transaction(function () use ($challenge, $user) {
            $locked = $this->lock($challenge);
            $this->ensureOpen($locked);
            $participant = $this->requireParticipant($locked, $user);

            if ($participant->accepted_match_at === null) {
                $participant->update(['accepted_match_at' => now()]);
                $locked->setRelation('participants', $locked->participants()->with('user')->get());
            }

            return $this->finalizeIfReady($locked);
        });
    }

    public function acceptScore(MatchChallenge $challenge, User $user): MatchChallenge
    {
        return DB::transaction(function () use ($challenge, $user) {
            $locked = $this->lock($challenge);
            $this->ensureOpen($locked);
            $participant = $this->requireParticipant($locked, $user);

            if (! $locked->hasValidScores()) {
                $setsToWin = $locked->rankingList?->getMaxSetsToWin() ?? 2;
                throw new InvalidArgumentException("Enter a finished match before accepting the score. First side to win {$setsToWin} sets.");
            }

            if ($participant->accepted_score_at === null) {
                $participant->update(['accepted_score_at' => now()]);
                $locked->setRelation('participants', $locked->participants()->with('user')->get());
            }

            return $this->finalizeIfReady($locked);
        });
    }

    public function decline(MatchChallenge $challenge, User $user): MatchChallenge
    {
        return DB::transaction(function () use ($challenge, $user) {
            $locked = $this->lock($challenge);
            $this->ensureOpen($locked);
            $participant = $this->requireParticipant($locked, $user);

            if ($participant->role === MatchChallengeParticipant::ROLE_CHALLENGER) {
                throw new InvalidArgumentException('Cancel the request if you no longer want to play.');
            }

            $locked->update(['status' => MatchChallenge::STATUS_DECLINED]);

            return $locked->fresh(['participants.user', 'rankingList', 'referee']);
        });
    }

    public function cancel(MatchChallenge $challenge, User $user): MatchChallenge
    {
        return DB::transaction(function () use ($challenge) {
            $locked = $this->lock($challenge);
            $this->ensureOpen($locked);

            $locked->update(['status' => MatchChallenge::STATUS_CANCELLED]);

            return $locked->fresh(['participants.user', 'rankingList', 'referee']);
        });
    }

    protected function finalizeIfReady(MatchChallenge $locked): MatchChallenge
    {
        $locked->loadMissing('rankingList');

        if (! $locked->readyToFinalize()) {
            return $locked->fresh(['participants.user', 'rankingList', 'referee', 'matchRecord']) ?? $locked;
        }

        $list = $locked->rankingList;
        $side1 = $locked->sidePlayers(1);
        $side2 = $locked->sidePlayers(2);
        $expected = $list->type === 'doubles' ? 2 : 1;

        if ($side1->count() !== $expected || $side2->count() !== $expected) {
            throw new InvalidArgumentException('This match is missing a player.');
        }

        if ($list->type === 'doubles') {
            $side1Ranking = PlayerRanking::findOrCreatePair($list, (int) $side1[0]->user_id, (int) $side1[1]->user_id);
            $side2Ranking = PlayerRanking::findOrCreatePair($list, (int) $side2[0]->user_id, (int) $side2[1]->user_id);
        } else {
            $side1Ranking = PlayerRanking::findOrCreateIndividual($list, (int) $side1[0]->user_id);
            $side2Ranking = PlayerRanking::findOrCreateIndividual($list, (int) $side2[0]->user_id);
        }

        $referee = $locked->participants->firstWhere('role', MatchChallengeParticipant::ROLE_REFEREE)?->user;

        $match = MatchRecord::create([
            'ranking_list_id' => $list->id,
            'match_type' => $list->type,
            'side1_ranking_id' => $side1Ranking->id,
            'side2_ranking_id' => $side2Ranking->id,
            'manager_id' => $locked->referee_id,
            'status' => 'in_progress',
            'played_at' => now(),
            'notes' => 'Member challenge. Referee: '.($referee?->publicName() ?? 'referee').'.',
        ]);

        foreach (array_values($locked->scores ?? []) as $index => $set) {
            $side1Score = (int) $set['side1_score'];
            $side2Score = (int) $set['side2_score'];

            MatchSet::create([
                'match_record_id' => $match->id,
                'set_number' => $index + 1,
                'side1_score' => $side1Score,
                'side2_score' => $side2Score,
                'winner_side' => TableTennisScoring::determineSetWinner($side1Score, $side2Score),
            ]);
        }

        (new EloService())->processMatchResult(
            $match->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking'])
        );

        $locked->update([
            'status' => MatchChallenge::STATUS_COMPLETED,
            'match_record_id' => $match->id,
        ]);

        return $locked->fresh(['participants.user', 'rankingList', 'referee', 'matchRecord']);
    }

    /**
     * @param  list<int>  $playerIds
     */
    protected function guardAgainstDuplicate(RankingList $list, array $playerIds): void
    {
        $wanted = collect($playerIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        $open = MatchChallenge::query()
            ->where('ranking_list_id', $list->id)
            ->where('status', MatchChallenge::STATUS_OPEN)
            ->with('participants')
            ->get();

        foreach ($open as $challenge) {
            $existing = $challenge->participants
                ->whereNotNull('side')
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($existing === $wanted) {
                throw new InvalidArgumentException('These players already have an open match on this list.');
            }
        }
    }

    /**
     * @param  array<int, array{side1_score?: mixed, side2_score?: mixed}>  $sets
     * @return list<array{side1_score: int, side2_score: int}>
     */
    protected function parseSets(MatchChallenge $challenge, array $sets): array
    {
        $list = $challenge->rankingList;
        $max = $list?->getTotalSetsCount() ?? 3;
        $setsToWin = $list?->getMaxSetsToWin() ?? 2;

        if (count($sets) > $max) {
            throw new InvalidArgumentException("This match allows at most {$max} sets.");
        }

        $rows = [];
        $skipped = false;

        foreach (array_values($sets) as $index => $set) {
            $raw1 = $set['side1_score'] ?? null;
            $raw2 = $set['side2_score'] ?? null;
            $blank1 = $raw1 === null || $raw1 === '';
            $blank2 = $raw2 === null || $raw2 === '';

            if ($blank1 && $blank2) {
                $skipped = true;
                continue;
            }

            if ($blank1 || $blank2) {
                throw new InvalidArgumentException('Set '.($index + 1).' needs both scores.');
            }

            if ($skipped) {
                throw new InvalidArgumentException('Fill the set scores in order, without skipping a set.');
            }

            $side1 = (int) $raw1;
            $side2 = (int) $raw2;

            if (! TableTennisScoring::isValidCompleteScore($side1, $side2)) {
                throw new InvalidArgumentException('Set '.($index + 1).': '.TableTennisScoring::validationMessage($side1, $side2));
            }

            $rows[] = [
                'side1_score' => $side1,
                'side2_score' => $side2,
            ];
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Enter at least one completed set.');
        }

        $side1Wins = 0;
        $side2Wins = 0;

        foreach ($rows as $row) {
            if ($side1Wins >= $setsToWin || $side2Wins >= $setsToWin) {
                throw new InvalidArgumentException("Stop once a side has won {$setsToWin} sets.");
            }

            if ($row['side1_score'] > $row['side2_score']) {
                $side1Wins++;
            } else {
                $side2Wins++;
            }
        }

        return $rows;
    }

    protected function lock(MatchChallenge $challenge): MatchChallenge
    {
        $locked = MatchChallenge::query()->whereKey($challenge->id)->lockForUpdate()->firstOrFail();
        $locked->load(['participants.user', 'rankingList', 'referee']);

        return $locked;
    }

    protected function ensureOpen(MatchChallenge $challenge): void
    {
        if (! $challenge->isOpen()) {
            throw new InvalidArgumentException('This match is already closed.');
        }
    }

    protected function requireParticipant(MatchChallenge $challenge, User $user): MatchChallengeParticipant
    {
        $participant = $challenge->participants->firstWhere('user_id', $user->id);

        if (! $participant) {
            throw new AuthorizationException('Only the players and the referee can accept this match.');
        }

        return $participant;
    }

    protected function activeMember(int $userId, string $message): User
    {
        $user = User::query()->where('status', 'active')->find($userId);

        if (! $user) {
            throw new InvalidArgumentException($message);
        }

        return $user;
    }

    /**
     * @return array{user_id: int, role: string, side: int|null, accepted_match_at: \Illuminate\Support\Carbon|null}
     */
    protected function participantRow(int $userId, string $role, ?int $side, bool $acceptedMatch): array
    {
        return [
            'user_id' => $userId,
            'role' => $role,
            'side' => $side,
            'accepted_match_at' => $acceptedMatch ? now() : null,
        ];
    }
}
