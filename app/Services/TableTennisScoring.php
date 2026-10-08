<?php

namespace App\Services;

class TableTennisScoring
{
    /**
     * ITTF game rules: first to 11, must win by 2.
     * Valid examples: 11-0..11-9, 12-10, 13-11, 14-12, ...
     * Invalid examples: 10-8, 11-10, 11-14, 12-9, 13-10.
     */
    public static function isValidCompleteScore(int $side1, int $side2): bool
    {
        if ($side1 < 0 || $side2 < 0 || $side1 === $side2) {
            return false;
        }

        $high = max($side1, $side2);
        $low = min($side1, $side2);

        if ($high < 11) {
            return false;
        }

        // Before deuce: winner must finish exactly at 11 with opponent ≤ 9.
        if ($low < 10) {
            return $high === 11;
        }

        // At/after deuce: must win by exactly 2 (game ends immediately).
        return ($high - $low) === 2;
    }

    public static function determineSetWinner(int $side1, int $side2): ?int
    {
        if (! self::isValidCompleteScore($side1, $side2)) {
            return null;
        }

        return $side1 > $side2 ? 1 : 2;
    }

    public static function validationMessage(int $side1, int $side2): string
    {
        $high = max($side1, $side2);
        $low = min($side1, $side2);

        if ($side1 === $side2) {
            return 'Scores cannot be tied — a set must have a winner.';
        }

        if ($high < 11) {
            return 'A set must reach at least 11 points to be complete.';
        }

        if ($low < 10 && $high !== 11) {
            return 'If the loser has fewer than 10 points, the winner must have exactly 11.';
        }

        if ($low >= 10 && ($high - $low) !== 2) {
            return 'After deuce (10-10), the winner must lead by exactly 2 points.';
        }

        return 'Invalid table tennis set score.';
    }

    /**
     * @param  array<int, array{side1_score?: mixed, side2_score?: mixed, winner_side?: mixed}>  $sets
     */
    public static function isValidMatchResult(array $sets, int $setsToWin): bool
    {
        if (count($sets) < $setsToWin || count($sets) > ($setsToWin * 2 - 1)) {
            return false;
        }

        $side1Wins = 0;
        $side2Wins = 0;

        foreach ($sets as $set) {
            $s1 = (int) ($set['side1_score'] ?? -1);
            $s2 = (int) ($set['side2_score'] ?? -1);

            if (! self::isValidCompleteScore($s1, $s2)) {
                return false;
            }

            // Match should already be decided — no extra sets after that.
            if ($side1Wins >= $setsToWin || $side2Wins >= $setsToWin) {
                return false;
            }

            if ($s1 > $s2) {
                $side1Wins++;
            } else {
                $side2Wins++;
            }
        }

        return ($side1Wins === $setsToWin && $side2Wins < $setsToWin)
            || ($side2Wins === $setsToWin && $side1Wins < $setsToWin);
    }

    /**
     * Default empty set rows for a ranking list format (minimum sets needed to decide).
     *
     * @return list<array{set_number: int, side1_score: null, side2_score: null, winner_side: null}>
     */
    public static function defaultSetRows(int $count): array
    {
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'set_number' => $i,
                'side1_score' => null,
                'side2_score' => null,
                'winner_side' => null,
            ];
        }

        return $rows;
    }

    /**
     * Filament repeater state keyed by UUID.
     *
     * @return array<string, array{set_number: int, side1_score: null, side2_score: null, winner_side: null}>
     */
    public static function defaultRepeaterState(int $count): array
    {
        $items = [];

        foreach (self::defaultSetRows($count) as $row) {
            $items[(string) \Illuminate\Support\Str::uuid()] = $row;
        }

        return $items;
    }
}
