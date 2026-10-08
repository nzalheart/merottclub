<?php

namespace App\Filament\Resources\MatchRecordResource\Pages;

use App\Filament\Resources\MatchRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMatchRecords extends ListRecords
{
    protected static string $resource = MatchRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
