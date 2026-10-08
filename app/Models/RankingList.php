<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type', // singles, doubles
        'format', // best_of_3, best_of_5, best_of_7
        'initial_elo',
        'k_factor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'initial_elo' => 'integer',
        'k_factor' => 'integer',
    ];

    public function rankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class)->orderBy('elo_rating', 'desc');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class)->orderBy('played_at', 'desc');
    }

    public function getMaxSetsToWin(): int
    {
        return match ($this->format) {
            'best_of_3' => 2,
            'best_of_7' => 4,
            default => 3, // best_of_5
        };
    }

    public function getTotalSetsCount(): int
    {
        return match ($this->format) {
            'best_of_3' => 3,
            'best_of_7' => 7,
            default => 5,
        };
    }

    /**
     * Minimum sets shown in the scorekeeper (enough for a straight-sets win).
     */
    public function getDefaultSetsToShow(): int
    {
        return $this->getMaxSetsToWin();
    }
}
