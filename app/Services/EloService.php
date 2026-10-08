<?php

namespace App\Services;

use App\Models\EloHistory;
use App\Models\MatchRecord;
use App\Models\PlayerRanking;

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
     * Doubles lists also update each partner's individual ranking using avg-vs-avg.
     */
    public function processMatchResult(MatchRecord $match): void
    {
        $rankingList = $match->rankingList;
        $side1Ranking = $match->side1Ranking;
        $side2Ranking = $match->side2Ranking;

        if (! $rankingList || ! $side1Ranking || ! $side2Ranking) {
            return;
        }

        $side1SetsWon = $match->sets()->where('winner_side', 1)->count();
        $side2SetsWon = $match->sets()->where('winner_side', 2)->count();

        $maxSetsToWin = $rankingList->getMaxSetsToWin();

        $winnerSide = null;
        if ($side1SetsWon >= $maxSetsToWin) {
            $winnerSide = 1;
        } elseif ($side2SetsWon >= $maxSetsToWin) {
            $winnerSide = 2;
        } else {
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

        $this->applyRankingResult(
            $side1Ranking,
            $newR1,
            $change1,
            $winnerSide === 1,
            $side1SetsWon,
            $side2SetsWon,
            $rankingList->id,
            $match->id,
            $r1,
        );

        $this->applyRankingResult(
            $side2Ranking,
            $newR2,
            $change2,
            $winnerSide === 2,
            $side2SetsWon,
            $side1SetsWon,
            $rankingList->id,
            $match->id,
            $r2,
        );

        if ($rankingList->type === 'doubles' && $side1Ranking->isPair() && $side2Ranking->isPair()) {
            $this->updateDoublesIndividuals(
                $side1Ranking,
                $side2Ranking,
                $winnerSide,
                $side1SetsWon,
                $side2SetsWon,
                $kFactor,
                $rankingList->id,
                $match->id,
            );
        }
    }

    protected function applyRankingResult(
        PlayerRanking $ranking,
        int $newElo,
        int $change,
        bool $won,
        int $setsWon,
        int $setsLost,
        int $rankingListId,
        int $matchId,
        int $oldElo,
    ): void {
        $ranking->update([
            'elo_rating' => $newElo,
            'matches_played' => $ranking->matches_played + 1,
            'wins' => $ranking->wins + ($won ? 1 : 0),
            'losses' => $ranking->losses + ($won ? 0 : 1),
            'sets_won' => $ranking->sets_won + $setsWon,
            'sets_lost' => $ranking->sets_lost + $setsLost,
            'current_streak' => $ranking->nextStreak($won),
        ]);

        EloHistory::create([
            'ranking_list_id' => $rankingListId,
            'match_record_id' => $matchId,
            'player_ranking_id' => $ranking->id,
            'old_elo' => $oldElo,
            'new_elo' => $newElo,
            'elo_change' => $change,
        ]);
    }

    /**
     * Apply the same team-vs-team delta to each partner's individual doubles ranking.
     */
    protected function updateDoublesIndividuals(
        PlayerRanking $side1Pair,
        PlayerRanking $side2Pair,
        int $winnerSide,
        int $side1SetsWon,
        int $side2SetsWon,
        int $kFactor,
        int $rankingListId,
        int $matchId,
    ): void {
        $side1Partners = $side1Pair->individualPartners();
        $side2Partners = $side2Pair->individualPartners();

        if (! $side1Partners || ! $side2Partners) {
            return;
        }

        [$s1a, $s1b] = $side1Partners;
        [$s2a, $s2b] = $side2Partners;

        $avg1 = (int) round(($s1a->elo_rating + $s1b->elo_rating) / 2);
        $avg2 = (int) round(($s2a->elo_rating + $s2b->elo_rating) / 2);

        $preview = self::preview($avg1, $avg2, $kFactor, $winnerSide);
        $delta1 = $preview['side1']['projected_delta'] ?? 0;
        $delta2 = $preview['side2']['projected_delta'] ?? 0;

        foreach ([$s1a, $s1b] as $individual) {
            $old = $individual->elo_rating;
            $new = $old + $delta1;
            $this->applyRankingResult(
                $individual,
                $new,
                $delta1,
                $winnerSide === 1,
                $side1SetsWon,
                $side2SetsWon,
                $rankingListId,
                $matchId,
                $old,
            );
        }

        foreach ([$s2a, $s2b] as $individual) {
            $old = $individual->elo_rating;
            $new = $old + $delta2;
            $this->applyRankingResult(
                $individual,
                $new,
                $delta2,
                $winnerSide === 2,
                $side2SetsWon,
                $side1SetsWon,
                $rankingListId,
                $matchId,
                $old,
            );
        }
    }
}
