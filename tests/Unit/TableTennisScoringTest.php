<?php

namespace Tests\Unit;

use App\Services\TableTennisScoring;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TableTennisScoringTest extends TestCase
{
    #[DataProvider('validScoresProvider')]
    public function test_valid_complete_scores(int $s1, int $s2, int $winner): void
    {
        $this->assertTrue(TableTennisScoring::isValidCompleteScore($s1, $s2));
        $this->assertSame($winner, TableTennisScoring::determineSetWinner($s1, $s2));
    }

    public static function validScoresProvider(): array
    {
        return [
            '11-0' => [11, 0, 1],
            '11-9' => [11, 9, 1],
            '0-11' => [0, 11, 2],
            '5-11' => [5, 11, 2],
            '12-10' => [12, 10, 1],
            '10-12' => [10, 12, 2],
            '13-11' => [13, 11, 1],
            '14-12' => [14, 12, 1],
            '20-18' => [20, 18, 1],
        ];
    }

    #[DataProvider('invalidScoresProvider')]
    public function test_invalid_scores(int $s1, int $s2): void
    {
        $this->assertFalse(TableTennisScoring::isValidCompleteScore($s1, $s2));
        $this->assertNull(TableTennisScoring::determineSetWinner($s1, $s2));
    }

    public static function invalidScoresProvider(): array
    {
        return [
            'tied 0-0' => [0, 0],
            'tied 10-10' => [10, 10],
            'incomplete 10-8' => [10, 8],
            'incomplete 10-9' => [10, 9],
            'not win by 2: 11-10' => [11, 10],
            'not win by 2: 12-11' => [12, 11],
            'past end: 12-9' => [12, 9],
            'past end: 13-10' => [13, 10],
            'user example 11-14' => [11, 14],
            'user example 10-13' => [10, 13],
            'wrong margin after deuce 14-11' => [14, 11],
            'negative' => [-1, 11],
        ];
    }

    public function test_valid_best_of_3_match(): void
    {
        $sets = [
            ['side1_score' => 11, 'side2_score' => 7],
            ['side1_score' => 11, 'side2_score' => 9],
        ];

        $this->assertTrue(TableTennisScoring::isValidMatchResult($sets, 2));
    }

    public function test_valid_best_of_5_three_one(): void
    {
        $sets = [
            ['side1_score' => 11, 'side2_score' => 5],
            ['side1_score' => 8, 'side2_score' => 11],
            ['side1_score' => 11, 'side2_score' => 9],
            ['side1_score' => 12, 'side2_score' => 10],
        ];

        $this->assertTrue(TableTennisScoring::isValidMatchResult($sets, 3));
    }

    public function test_rejects_incomplete_match(): void
    {
        $sets = [
            ['side1_score' => 11, 'side2_score' => 7],
        ];

        $this->assertFalse(TableTennisScoring::isValidMatchResult($sets, 2));
    }

    public function test_rejects_extra_sets_after_decided(): void
    {
        $sets = [
            ['side1_score' => 11, 'side2_score' => 7],
            ['side1_score' => 11, 'side2_score' => 9],
            ['side1_score' => 11, 'side2_score' => 4],
        ];

        $this->assertFalse(TableTennisScoring::isValidMatchResult($sets, 2));
    }

    public function test_default_set_rows_count(): void
    {
        $rows = TableTennisScoring::defaultSetRows(3);

        $this->assertCount(3, $rows);
        $this->assertSame(1, $rows[0]['set_number']);
        $this->assertSame(3, $rows[2]['set_number']);
        $this->assertNull($rows[0]['side1_score']);
    }

    public function test_default_repeater_state_uses_uuid_keys(): void
    {
        $state = TableTennisScoring::defaultRepeaterState(2);

        $this->assertCount(2, $state);
        foreach (array_keys($state) as $key) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $key,
            );
        }
    }
}
