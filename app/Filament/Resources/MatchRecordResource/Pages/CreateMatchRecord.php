<?php

namespace App\Filament\Resources\MatchRecordResource\Pages;

use App\Filament\Resources\MatchRecordResource;
use App\Filament\Resources\MatchRecordResource\Concerns\SyncsMatchSets;
use App\Models\RankingList;
use App\Services\EloService;
use Filament\Resources\Pages\CreateRecord;

class CreateMatchRecord extends CreateRecord
{
    use SyncsMatchSets;

    protected static string $resource = MatchRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->extractSetsFromFormData($data);
        $data = MatchRecordResource::resolveDoublesSides($data, $this->form->getRawState());

        $list = RankingList::find($data['ranking_list_id'] ?? null);
        $data['match_type'] = $list?->type ?? ($data['match_type'] ?? 'singles');
        $data['manager_id'] = auth()->id();
        $data['played_at'] = now();
        $data['status'] = 'in_progress';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSetsFromPayload();

        $eloService = new EloService();
        $eloService->processMatchResult($this->record->fresh(['sets', 'rankingList', 'side1Ranking', 'side2Ranking']));
    }
}
