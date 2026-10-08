<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ranking_list_id',
        'match_record_id',
        'player_ranking_id',
        'old_elo',
        'new_elo',
        'elo_change',
    ];

    protected $casts = [
        'old_elo' => 'integer',
        'new_elo' => 'integer',
        'elo_change' => 'integer',
    ];

    public function rankingList(): BelongsTo
    {
        return $this->belongsTo(RankingList::class);
    }

    public function matchRecord(): BelongsTo
    {
        return $this->belongsTo(MatchRecord::class);
    }

    public function playerRanking(): BelongsTo
    {
        return $this->belongsTo(PlayerRanking::class);
    }
}
