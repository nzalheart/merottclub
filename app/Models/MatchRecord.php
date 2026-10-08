<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'ranking_list_id',
        'match_type',
        'side1_ranking_id',
        'side2_ranking_id',
        'winner_side',
        'manager_id',
        'status',
        'side1_sets_won',
        'side2_sets_won',
        'side1_elo_before',
        'side1_elo_after',
        'side2_elo_before',
        'side2_elo_after',
        'played_at',
        'notes',
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'winner_side' => 'integer',
        'side1_sets_won' => 'integer',
        'side2_sets_won' => 'integer',
        'side1_elo_before' => 'integer',
        'side1_elo_after' => 'integer',
        'side2_elo_before' => 'integer',
        'side2_elo_after' => 'integer',
    ];

    public function rankingList(): BelongsTo
    {
        return $this->belongsTo(RankingList::class);
    }

    public function side1Ranking(): BelongsTo
    {
        return $this->belongsTo(PlayerRanking::class, 'side1_ranking_id');
    }

    public function side2Ranking(): BelongsTo
    {
        return $this->belongsTo(PlayerRanking::class, 'side2_ranking_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(MatchSet::class)->orderBy('set_number', 'asc');
    }

    public function eloHistories(): HasMany
    {
        return $this->hasMany(EloHistory::class);
    }
}
