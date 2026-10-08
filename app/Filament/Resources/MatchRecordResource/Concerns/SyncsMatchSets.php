<?php

namespace App\Filament\Resources\MatchRecordResource\Concerns;

use App\Services\TableTennisScoring;

trait SyncsMatchSets
{
    /**
     * @var list<array<string, mixed>>|null
     */
    protected ?array $setsPayload = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractSetsFromFormData(array $data): array
    {
        $this->setsPayload = array_values($data['sets'] ?? []);
        unset($data['sets']);

        return $data;
    }

    protected function syncSetsFromPayload(): void
    {
        if ($this->setsPayload === null || ! $this->record) {
            return;
        }

        $this->record->sets()->delete();

        $setNumber = 1;

        foreach ($this->setsPayload as $set) {
            $side1 = (int) ($set['side1_score'] ?? -1);
            $side2 = (int) ($set['side2_score'] ?? -1);

            $this->record->sets()->create([
                'set_number' => $setNumber++,
                'side1_score' => $side1,
                'side2_score' => $side2,
                'winner_side' => TableTennisScoring::determineSetWinner($side1, $side2),
            ]);
        }
    }
}
