<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchChallengeParticipant extends Model
{
    public const ROLE_CHALLENGER = 'challenger';

    public const ROLE_PARTNER = 'partner';

    public const ROLE_OPPONENT = 'opponent';

    public const ROLE_OPPONENT_PARTNER = 'opponent_partner';

    public const ROLE_REFEREE = 'referee';

    protected $fillable = [
        'match_challenge_id',
        'user_id',
        'role',
        'side',
        'accepted_match_at',
        'accepted_score_at',
    ];

    protected $casts = [
        'side' => 'integer',
        'accepted_match_at' => 'datetime',
        'accepted_score_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(MatchChallenge::class, 'match_challenge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_CHALLENGER => 'Challenger',
            self::ROLE_PARTNER => 'Partner',
            self::ROLE_OPPONENT => 'Opponent',
            self::ROLE_OPPONENT_PARTNER => 'Opponent partner',
            self::ROLE_REFEREE => 'Referee',
            default => ucfirst((string) $this->role),
        };
    }
}
