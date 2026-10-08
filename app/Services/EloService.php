<?php

namespace App\Services;

use App\Models\EloHistory;
use App\Models\MatchRecord;

class EloService
{
    /**
     * Preview ELO before saving a match.
     *
     * @return array{
     *     side1: array{current: int, win_delta: int, lose_delta: int, projected: ?int, projected_delta: ?int},
     *     side2: array{current: int, win_delta: int, lose_delta: int, projected: ?int, projected_delta: ?int},
     *     winner_side: ?int
     * }
     */
    public static function preview(int $rating1, int $rating2, int $kFactor = 32, ?int $winnerSide = null): array
    {
        $expected1 = 1 / (1 + pow(10, ($rating2 - $rating1) / 400));
        $expected2 = 1 / (1 + pow(10, ($rating1 - $rating2) / 400));

        $side1 = [
            'current' => $rating1,
            'win_delta' => (int) round($kFactor * (1 - $expected1)),
            'lose_delta' => (int) round($kFactor * (0 - $expected1)),
            'projected' => null,
            'projected_delta' => null,
        ];

        $side2 = [
            'current' => $rating2,
            'win_delta' => (int) round($kFactor * (1 - $expected2)),
            'lose_delta' => (int) round($kFactor * (0 - $expected2)),
            'projected' => null,
            'projected_delta' => null,
        ];

        if ($winnerSide === 1 || $winnerSide === 2) {
            $actual1 = $winnerSide === 1 ? 1 : 0;
            $actual2 = $winnerSide === 2 ? 1 : 0;
            $new1 = (int) round($rating1 + $kFactor * ($actual1 - $expected1));
            $new2 = (int) round($rating2 + $kFactor * ($actual2 - $expected2));

            $side1['projected'] = $new1;
            $side1['projected_delta'] = $new1 - $rating1;
            $side2['projected'] = $new2;
            $side2['projected_delta'] = $new2 - $rating2;
        }

        return [
            'side1' => $side1,
            'side2' => $side2,
            'winner_side' => $winnerSide,
        ];
    }

    /**
     * Finalize match and calculate ELO updates for both sides.
     */
    public function processMatchResult(MatchRecord $match): void
    {
        $rankingList = $match->rankingList;
        $side1Ranking = $match->side1Ranking;
        $side2Ranking = $match->side2Ranking;

        if (!$rankingList || !$side1Ranking || !$side2Ranking) {
            return;
        }

        // Calculate sets won
        $side1SetsWon = $match->sets()->where('winner_side', 1)->count();
        $side2SetsWon = $match->sets()->where('winner_side', 2)->count();

        $maxSetsToWin = $rankingList->getMaxSetsToWin();

        $winnerSide = null;
        if ($side1SetsWon >= $maxSetsToWin) {
            $winnerSide = 1;
        } elseif ($side2SetsWon >= $maxSetsToWin) {
            $winnerSide = 2;
        } else {
            // Determine winner by highest sets won if match finished early
            $winnerSide = $side1SetsWon > $side2SetsWon ? 1 : ($side2SetsWon > $side1SetsWon ? 2 : 1);
        }

        $r1 = $side1Ranking->elo_rating;
        $r2 = $side2Ranking->elo_rating;

        $kFactor = $rankingList->k_factor ?? 32;

        $preview = self::preview($r1, $r2, $kFactor, $winnerSide);
        $newR1 = $preview['side1']['projected'] ?? $r1;
        $newR2 = $preview['side2']['projected'] ?? $r2;
        $change1 = $preview['side1']['projected_delta'] ?? 0;
        $change2 = $preview['side2']['projected_delta'] ?? 0;

        // Update MatchRecord
        $match->update([
            'winner_side' => $winnerSide,
            'side1_sets_won' => $side1SetsWon,
            'side2_sets_won' => $side2SetsWon,
            'side1_elo_before' => $r1,
            'side1_elo_after' => $newR1,
            'side2_elo_before' => $r2,
            'side2_elo_after' => $newR2,
            'status' => 'completed',
            'played_at' => $match->played_at ?? now(),
        ]);

        // Update Side 1 Player Ranking
        $side1Ranking->update([
            'elo_rating' => $newR1,
            'matches_played' => $side1Ranking->matches_played + 1,
            'wins' => $side1Ranking->wins + ($winnerSide === 1 ? 1 : 0),
            'losses' => $side1Ranking->losses + ($winnerSide === 1 ? 0 : 1),
            'sets_won' => $side1Ranking->sets_won + $side1SetsWon,
            'sets_lost' => $side1Ranking->sets_lost + $side2SetsWon,
        ]);

        // Update Side 2 Player Ranking
        $side2Ranking->update([
            'elo_rating' => $newR2,
            'matches_played' => $side2Ranking->matches_played + 1,
            'wins' => $side2Ranking->wins + ($winnerSide === 2 ? 1 : 0),
            'losses' => $side2Ranking->losses + ($winnerSide === 2 ? 0 : 1),
            'sets_won' => $side2Ranking->sets_won + $side2SetsWon,
            'sets_lost' => $side2Ranking->sets_lost + $side1SetsWon,
        ]);

        // Create ELO History Logs
        EloHistory::create([
            'ranking_list_id' => $rankingList->id,
            'match_record_id' => $match->id,
            'player_ranking_id' => $side1Ranking->id,
            'old_elo' => $r1,
            'new_elo' => $newR1,
            'elo_change' => $change1,
        ]);

        EloHistory::create([
            'ranking_list_id' => $rankingList->id,
            'match_record_id' => $match->id,
            'player_ranking_id' => $side2Ranking->id,
            'old_elo' => $r2,
            'new_elo' => $newR2,
            'elo_change' => $change2,
        ]);
    }
}
