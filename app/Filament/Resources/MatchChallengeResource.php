<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatchChallengeResource\Pages;
use App\Models\MatchChallenge;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MatchChallengeResource extends Resource
{
    protected static ?string $model = MatchChallenge::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Match Scorekeeper';

    protected static ?string $navigationLabel = 'Active matches';

    protected static ?string $modelLabel = 'Active match';

    protected static ?string $pluralModelLabel = 'Active matches';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'active-matches';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isManager();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', MatchChallenge::STATUS_OPEN)
            ->with([
                'rankingList',
                'initiator',
                'referee',
                'participants.user',
            ])
            ->latest();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rankingList.title')
                    ->label('Ranking list')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('match_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->color(fn (?string $state): string => $state === 'doubles' ? 'warning' : 'primary'),
                Tables\Columns\ViewColumn::make('sides')
                    ->label('Match')
                    ->view('filament.tables.columns.active-match-sides'),
                Tables\Columns\ViewColumn::make('scores')
                    ->label('Scores')
                    ->view('filament.tables.columns.active-match-scores'),
                Tables\Columns\ViewColumn::make('score_acceptance')
                    ->label('Score acceptance')
                    ->view('filament.tables.columns.active-match-acceptance'),
                Tables\Columns\TextColumn::make('phase')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (MatchChallenge $record): string => $record->phaseLabel())
                    ->color(fn (MatchChallenge $record): string => match (true) {
                        ! $record->hasValidScores() => 'gray',
                        ! $record->allAcceptedScore() => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->since()
                    ->sortable()
                    ->description(fn (MatchChallenge $record): string => $record->created_at?->format('M j, Y H:i') ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MatchChallenge $record): string => route('matches.challenge', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActiveMatches::route('/'),
        ];
    }
}
