<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_record_id',
        'set_number',
        'side1_score',
        'side2_score',
        'winner_side',
    ];

    protected $casts = [
        'set_number' => 'integer',
        'side1_score' => 'integer',
        'side2_score' => 'integer',
        'winner_side' => 'integer',
    ];

    public function matchRecord(): BelongsTo
    {
        return $this->belongsTo(MatchRecord::class);
    }
}
