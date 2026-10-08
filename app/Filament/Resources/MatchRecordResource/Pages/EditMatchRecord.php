<?php

namespace App\Filament\Resources\MatchRecordResource\Pages;

use App\Filament\Resources\MatchRecordResource;
use App\Filament\Resources\MatchRecordResource\Concerns\SyncsMatchSets;
use App\Services\EloService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatchRecord extends EditRecord
{
    use SyncsMatchSets;

    protected static string $resource = MatchRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['sets'] = $this->record->sets
            ->map(fn ($set) => [
                'set_number' => $set->set_number,
                'side1_score' => $set->side1_score,
                'side2_score' => $set->side2_score,
                'winner_side' => $set->winner_side,
            ])
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractSetsFromFormData($data);
    }

    protected function afterSave(): void
    {
        $this->syncSetsFromPayload();

        $eloService = new EloService();
        $eloService->processMatchResult($this->record->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking']));
    }
}
