<?php

namespace App\Models;

use App\Services\TableTennisScoring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MatchChallenge extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public const WITHOUT_REFEREE_LABEL = 'Without referee - Mutual consent';

    protected $fillable = [
        'ranking_list_id',
        'match_type',
        'initiated_by',
        'referee_id',
        'status',
        'scores',
        'scores_saved_by',
        'scores_edited_by',
        'match_record_id',
    ];

    protected $casts = [
        'scores' => 'array',
    ];

    public function rankingList(): BelongsTo
    {
        return $this->belongsTo(RankingList::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function scoreSaver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scores_saved_by');
    }

    public function scoreEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scores_edited_by');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function hasReferee(): bool
    {
        return $this->referee_id !== null;
    }

    public function refereeLabel(): string
    {
        return $this->referee?->publicName() ?? self::WITHOUT_REFEREE_LABEL;
    }

    public function matchRecord(): BelongsTo
    {
        return $this->belongsTo(MatchRecord::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchChallengeParticipant::class);
    }

    public function scopeOpenInvolving(Builder $query, int $userId): Builder
    {
        return $query->where('status', self::STATUS_OPEN)
            ->whereHas('participants', fn (Builder $participants) => $participants->where('user_id', $userId));
    }

    /**
     * Every participant sees an open match as soon as it starts.
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->openInvolving($userId);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isDoubles(): bool
    {
        return $this->match_type === 'doubles';
    }

    public function participantFor(int $userId): ?MatchChallengeParticipant
    {
        return $this->participants->firstWhere('user_id', $userId);
    }

    /**
     * @return Collection<int, MatchChallengeParticipant>
     */
    public function sidePlayers(int $side): Collection
    {
        return $this->participants
            ->where('side', $side)
            ->sortBy(fn (MatchChallengeParticipant $participant) => in_array($participant->role, [
                MatchChallengeParticipant::ROLE_CHALLENGER,
                MatchChallengeParticipant::ROLE_OPPONENT,
            ], true) ? 0 : 1)
            ->values();
    }

    public function sideLabel(int $side): string
    {
        $names = $this->sidePlayers($side)
            ->map(fn (MatchChallengeParticipant $participant) => $participant->user?->publicName() ?? 'Player')
            ->all();

        return $names === [] ? 'Side '.$side : implode(' & ', $names);
    }

    public function hasSavedScores(): bool
    {
        return is_array($this->scores) && $this->scores !== [];
    }

    public function scoreButtonLabel(): string
    {
        return $this->hasSavedScores() ? 'Change scores' : 'Save scores';
    }

    public function wasLastEditedBy(?int $userId): bool
    {
        if ($userId === null || ! $this->hasSavedScores()) {
            return false;
        }

        return (int) ($this->scores_edited_by ?? $this->scores_saved_by) === $userId;
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, avatars: list<array{url: ?string, initials: string, name: string}>}
     */
    public function sideMatrixLabel(int $side): array
    {
        $players = $this->sidePlayers($side);
        $name = $players
            ->map(fn (MatchChallengeParticipant $participant) => $participant->user?->name ?: 'Player')
            ->implode(' & ');
        $nickname = $players
            ->map(fn (MatchChallengeParticipant $participant) => $participant->user?->nickname)
            ->filter()
            ->map(fn (string $nick) => '@'.$nick)
            ->implode(' / ');
        $avatars = $players
            ->map(fn (MatchChallengeParticipant $participant): array => $participant->user?->avatarPayload() ?? [
                'url' => null,
                'initials' => '?',
                'name' => 'Player',
            ])
            ->values()
            ->all();

        return [
            'name' => $name !== '' ? $name : 'Side '.$side,
            'nickname' => $nickname !== '' ? $nickname : null,
            'title' => $name !== '' ? $name : 'Side '.$side,
            'avatars' => $avatars,
        ];
    }

    public function sideRating(int $side): int
    {
        $list = $this->rankingList;
        $fallback = (int) ($list->initial_elo ?? 1200);
        $players = $this->sidePlayers($side);

        if (! $list || $players->isEmpty()) {
            return $fallback;
        }

        if ($this->isDoubles() && $players->count() === 2) {
            $low = min((int) $players[0]->user_id, (int) $players[1]->user_id);
            $high = max((int) $players[0]->user_id, (int) $players[1]->user_id);
            $pair = PlayerRanking::query()
                ->where('ranking_list_id', $list->id)
                ->where('user_id', $low)
                ->where('player2_id', $high)
                ->first();

            if ($pair) {
                return (int) $pair->elo_rating;
            }
        }

        $ratings = $players->map(function (MatchChallengeParticipant $participant) use ($list, $fallback) {
            $rating = PlayerRanking::query()
                ->where('ranking_list_id', $list->id)
                ->where('user_id', $participant->user_id)
                ->individuals()
                ->value('elo_rating');

            return $rating !== null ? (int) $rating : $fallback;
        });

        return (int) round($ratings->avg());
    }

    public function hasValidScores(): bool
    {
        $scores = $this->scores ?? [];
        $list = $this->rankingList;

        if ($scores === [] || ! $list) {
            return false;
        }

        return TableTennisScoring::isValidMatchResult($scores, $list->getMaxSetsToWin());
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    public function setTally(): ?array
    {
        $scores = $this->scores ?? [];

        if ($scores === []) {
            return null;
        }

        $side1 = 0;
        $side2 = 0;

        foreach ($scores as $set) {
            if ((int) $set['side1_score'] > (int) $set['side2_score']) {
                $side1++;
            } else {
                $side2++;
            }
        }

        return [$side1, $side2];
    }

    public function participantAcceptedScore(MatchChallengeParticipant $participant): bool
    {
        if ($participant->accepted_score_at !== null) {
            return true;
        }

        return $this->hasValidScores() && $this->wasLastEditedBy((int) $participant->user_id);
    }

    public function allAcceptedScore(): bool
    {
        return $this->participants->isNotEmpty()
            && $this->participants->every(fn (MatchChallengeParticipant $participant) => $this->participantAcceptedScore($participant));
    }

    public function readyToFinalize(): bool
    {
        return $this->isOpen()
            && $this->allAcceptedScore()
            && $this->hasValidScores();
    }

    public function phaseLabel(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'Saved to rankings',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_CANCELLED => 'Cancelled',
            default => $this->openPhaseLabel(),
        };
    }

    public function needsActionFrom(int $userId): bool
    {
        $participant = $this->participantFor($userId);

        if (! $participant || ! $this->isOpen()) {
            return false;
        }

        return $this->hasValidScores() && ! $this->participantAcceptedScore($participant);
    }

    protected function openPhaseLabel(): string
    {
        if (! $this->hasValidScores()) {
            return 'Waiting for scores';
        }

        if (! $this->allAcceptedScore()) {
            return 'Waiting for score acceptance';
        }

        return 'Ready to save';
    }
}
