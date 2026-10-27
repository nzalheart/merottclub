<?php

namespace App\Filament\Resources\MatchChallengeResource\Pages;

use App\Filament\Resources\MatchChallengeResource;
use Filament\Resources\Pages\ListRecords;

class ListActiveMatches extends ListRecords
{
    protected static string $resource = MatchChallengeResource::class;

    protected static ?string $title = 'Active matches';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
