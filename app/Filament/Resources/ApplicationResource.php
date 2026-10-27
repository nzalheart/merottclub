<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\User;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApplicationResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'Club Management';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $modelLabel = 'Application';

    protected static ?string $pluralModelLabel = 'Applications';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'applications';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isManager();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny()
            && $record instanceof User
            && $record->status === 'pending';
    }

    public static function approve(User $record): void
    {
        $record->update(['status' => 'active']);

        Notification::make()
            ->title('Application approved')
            ->body($record->name.' can sign in. An acceptance email was sent.')
            ->success()
            ->send();
    }

    public static function reject(User $record): void
    {
        $record->update(['status' => 'rejected']);

        Notification::make()
            ->title('Application rejected')
            ->body($record->name.' cannot sign in.')
            ->success()
            ->send();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'pending');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Full name'),
                        Infolists\Components\TextEntry::make('nickname'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label('Date of birth')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('gender')
                            ->formatStateUsing(fn (?string $state): string => User::GENDERS[$state] ?? '—')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('role')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Applied')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('name')
                    ->label('Applicant')
                    ->view('filament.tables.columns.player-name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->formatStateUsing(fn (?string $state): string => User::GENDERS[$state] ?? '—'),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (User $record): string => static::getUrl('view', ['record' => $record]))
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->button()
                    ->color('gray'),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->button()
                    ->action(fn (User $record) => static::approve($record)),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->button()
                    ->action(fn (User $record) => static::reject($record)),
            ])
            ->emptyStateHeading('No applications')
            ->emptyStateDescription('People who use Apply on the public site show up here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'view' => Pages\ViewApplication::route('/{record}'),
        ];
    }
}
