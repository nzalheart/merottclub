<?php

namespace App\Filament\Resources\MatchRecordResource\Pages;

use App\Filament\Resources\MatchRecordResource;
use App\Filament\Resources\MatchRecordResource\Concerns\SyncsMatchSets;
use App\Services\EloService;
use Filament\Resources\Pages\CreateRecord;

class CreateMatchRecord extends CreateRecord
{
    use SyncsMatchSets;

    protected static string $resource = MatchRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->extractSetsFromFormData($data);
        $data['manager_id'] = auth()->id();
        $data['played_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSetsFromPayload();

        $eloService = new EloService();
        $eloService->processMatchResult($this->record->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking']));
    }
}
