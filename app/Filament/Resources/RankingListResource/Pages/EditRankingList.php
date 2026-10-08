<?php

namespace App\Filament\Resources\RankingListResource\Pages;

use App\Filament\Resources\RankingListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRankingList extends EditRecord
{
    protected static string $resource = RankingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
