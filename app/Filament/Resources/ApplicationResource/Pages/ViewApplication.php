<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function (): void {
                    $record = $this->getRecord();

                    if ($record instanceof User) {
                        ApplicationResource::approve($record);
                    }

                    $this->redirect(ApplicationResource::getUrl('index'));
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->action(function (): void {
                    $record = $this->getRecord();

                    if ($record instanceof User) {
                        ApplicationResource::reject($record);
                    }

                    $this->redirect(ApplicationResource::getUrl('index'));
                }),
        ];
    }
}
