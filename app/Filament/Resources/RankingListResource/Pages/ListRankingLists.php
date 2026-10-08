<?php

namespace App\Filament\Resources\RankingListResource\Pages;

use App\Filament\Resources\RankingListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRankingLists extends ListRecords
{
    protected static string $resource = RankingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
