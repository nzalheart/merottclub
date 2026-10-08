<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'elo_rating' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'sets_won' => 'integer',
        'sets_lost' => 'integer',
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
