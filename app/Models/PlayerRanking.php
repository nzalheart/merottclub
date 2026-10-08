<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'ranking_list_id',
        'user_id',
        'player2_id',
        'team_name',
        'elo_rating',
        'matches_played',
        'wins',
        'losses',
        'sets_won',
        'sets_lost',
        'current_streak',
    ];

    protected $casts = [
        'elo_rating' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'sets_won' => 'integer',
        'sets_lost' => 'integer',
        'current_streak' => 'integer',
    ];

    public function rankingList(): BelongsTo
    {
        return $this->belongsTo(RankingList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function eloHistories(): HasMany
    {
        return $this->hasMany(EloHistory::class);
    }

    public function scopeIndividuals(Builder $query): Builder
    {
        return $query->whereNull('player2_id');
    }

    public function scopePairs(Builder $query): Builder
    {
        return $query->whereNotNull('player2_id');
    }

    public function isPair(): bool
    {
        return $this->player2_id !== null;
    }

    public function isIndividual(): bool
    {
        return $this->player2_id === null;
    }

    /**
     * Ensure an individual ranking exists on the list for the given user.
     */
    public static function findOrCreateIndividual(RankingList $list, int $userId): self
    {
        return static::firstOrCreate(
            [
                'ranking_list_id' => $list->id,
                'user_id' => $userId,
                'player2_id' => null,
            ],
            [
                'elo_rating' => $list->initial_elo,
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'sets_won' => 0,
                'sets_lost' => 0,
                'current_streak' => 0,
            ]
        );
    }

    /**
     * Find or create a pair ranking with canonical partner order (lower user id first).
     */
    public static function findOrCreatePair(RankingList $list, int $userA, int $userB): self
    {
        if ($userA === $userB) {
            throw new \InvalidArgumentException('A doubles pair must be two different players.');
        }

        [$p1, $p2] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

        static::findOrCreateIndividual($list, $p1);
        static::findOrCreateIndividual($list, $p2);

        $user1 = User::find($p1);
        $user2 = User::find($p2);
        $teamName = trim(($user1?->name ?? 'P1') . ' / ' . ($user2?->name ?? 'P2'));

        return static::firstOrCreate(
            [
                'ranking_list_id' => $list->id,
                'user_id' => $p1,
                'player2_id' => $p2,
            ],
            [
                'team_name' => $teamName,
                'elo_rating' => $list->initial_elo,
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'sets_won' => 0,
                'sets_lost' => 0,
                'current_streak' => 0,
            ]
        );
    }

    /**
     * Next streak value after a match result.
     * Positive = consecutive wins, negative = consecutive losses.
     */
    public function nextStreak(bool $won): int
    {
        $streak = (int) $this->current_streak;

        if ($won) {
            return $streak > 0 ? $streak + 1 : 1;
        }

        return $streak < 0 ? $streak - 1 : -1;
    }

    public function getStreakLabelAttribute(): ?string
    {
        $streak = (int) $this->current_streak;

        if ($streak === 0) {
            return null;
        }

        return $streak > 0 ? '+'.$streak : (string) $streak;
    }

    /**
     * Individual rankings for both partners of a pair row.
     *
     * @return array{0: self, 1: self}|null
     */
    public function individualPartners(): ?array
    {
        if (! $this->isPair()) {
            return null;
        }

        $list = $this->rankingList;
        if (! $list) {
            return null;
        }

        return [
            static::findOrCreateIndividual($list, (int) $this->user_id),
            static::findOrCreateIndividual($list, (int) $this->player2_id),
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->team_name) {
            return $this->team_name;
        }

        if ($this->player2) {
            $p1 = $this->user ? ($this->user->nickname ? "{$this->user->name} (@{$this->user->nickname})" : $this->user->name) : 'Player 1';
            $p2 = $this->player2 ? ($this->player2->nickname ? "{$this->player2->name} (@{$this->player2->nickname})" : $this->player2->name) : 'Player 2';

            return "{$p1} & {$p2}";
        }

        if ($this->user) {
            return $this->user->nickname ? "{$this->user->name} (@{$this->user->nickname})" : $this->user->name;
        }

        return 'Unknown Player';
    }

    public function getWinRateAttribute(): float
    {
        if ($this->matches_played === 0) {
            return 0.0;
        }

        return round(($this->wins / $this->matches_played) * 100, 1);
    }
}
