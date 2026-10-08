<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\SetScoreMatrix;
use App\Filament\Resources\MatchRecordResource\Pages;
use App\Models\MatchRecord;
use App\Models\PlayerRanking;
use App\Models\RankingList;
use App\Services\TableTennisScoring;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MatchRecordResource extends Resource
{
    protected static ?string $model = MatchRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Match Scorekeeper';

    protected static ?string $label = 'Match & Scorekeeper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Match Setup & Competitors')
                    ->description('Select the Ranking List and the competing sides.')
                    ->schema([
                        Forms\Components\Select::make('ranking_list_id')
                            ->label('Ranking List')
                            ->options(RankingList::where('is_active', true)->pluck('title', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                $list = $state ? RankingList::find($state) : null;
                                $newListId = $list?->id;

                                $mapped1 = $newListId
                                    ? static::findEquivalentRanking($get('side1_ranking_id'), (int) $newListId)
                                    : null;
                                $mapped2 = $newListId
                                    ? static::findEquivalentRanking($get('side2_ranking_id'), (int) $newListId)
                                    : null;

                                $set('side1_ranking_id', $mapped1?->id);
                                $set('side2_ranking_id', $mapped2?->id);

                                // Keep scores only when both players still exist on the new list.
                                if ($mapped1 && $mapped2) {
                                    return;
                                }

                                $count = $list?->getDefaultSetsToShow() ?? 2;
                                $set('sets', TableTennisScoring::defaultSetRows($count));
                            }),

                        Forms\Components\Select::make('side1_ranking_id')
                            ->label('Player / Side 1')
                            ->options(function (Get $get) {
                                $listId = $get('ranking_list_id');
                                if (! $listId) {
                                    return PlayerRanking::with(['user', 'player2'])->get()->pluck('display_name', 'id');
                                }

                                return PlayerRanking::where('ranking_list_id', $listId)->with(['user', 'player2'])->get()->pluck('display_name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('side2_ranking_id')
                            ->label('Player / Side 2')
                            ->options(function (Get $get) {
                                $listId = $get('ranking_list_id');
                                if (! $listId) {
                                    return PlayerRanking::with(['user', 'player2'])->get()->pluck('display_name', 'id');
                                }

                                return PlayerRanking::where('ranking_list_id', $listId)->with(['user', 'player2'])->get()->pluck('display_name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->different('side1_ranking_id')
                            ->live(),

                        SetScoreMatrix::make('sets')
                            ->hiddenLabel()
                            ->required()
                            ->columnSpanFull()
                            ->default(fn () => TableTennisScoring::defaultSetRows(2))
                            ->side1(fn (Get $get): array => static::sideDisplay($get, 1))
                            ->side2(fn (Get $get): array => static::sideDisplay($get, 2))
                            ->minSets(fn (Get $get): int => static::setsToWin($get))
                            ->maxSets(fn (Get $get): int => static::maxSets($get))
                            ->kFactor(fn (Get $get): int => static::kFactor($get))
                            ->matrixKey(fn (Get $get): string => implode('-', [
                                $get('ranking_list_id') ?? 'none',
                                $get('side1_ranking_id') ?? 's1',
                                $get('side2_ranking_id') ?? 's2',
                            ]))
                            ->rule(function (Get $get): \Closure {
                                return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                    $setsToWin = static::setsToWin($get);
                                    $sets = is_array($value) ? array_values($value) : [];

                                    foreach ($sets as $index => $set) {
                                        $s1 = (int) ($set['side1_score'] ?? -1);
                                        $s2 = (int) ($set['side2_score'] ?? -1);

                                        if (! TableTennisScoring::isValidCompleteScore($s1, $s2)) {
                                            $fail('Set ' . ($index + 1) . ': ' . TableTennisScoring::validationMessage($s1, $s2));

                                            return;
                                        }
                                    }

                                    if (! TableTennisScoring::isValidMatchResult($sets, $setsToWin)) {
                                        $fail("Enter a complete match result (first to {$setsToWin} sets). Stop once a side reaches {$setsToWin}.");
                                    }
                                };
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rankingList.title')
                    ->label('Ranking List')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('side1Ranking.display_name')
                    ->label('Side 1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('side1_sets_won')
                    ->label('Sets')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('side2_sets_won')
                    ->label('Sets')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('side2Ranking.display_name')
                    ->label('Side 2')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('winner_side')
                    ->label('Winner')
                    ->formatStateUsing(fn ($state, MatchRecord $record) => $state === 1 ? $record->side1Ranking?->display_name : ($state === 2 ? $record->side2Ranking?->display_name : 'Pending'))
                    ->colors([
                        'success' => fn ($state) => $state !== null,
                    ]),
                Tables\Columns\TextColumn::make('side1_elo_after')
                    ->label('Side 1 ELO')
                    ->formatStateUsing(fn ($state, MatchRecord $record) => $record->side1_elo_before . ' → ' . $state),
                Tables\Columns\TextColumn::make('side2_elo_after')
                    ->label('Side 2 ELO')
                    ->formatStateUsing(fn ($state, MatchRecord $record) => $record->side2_elo_before . ' → ' . $state),
                Tables\Columns\TextColumn::make('played_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ranking_list_id')
                    ->label('Filter Ranking List')
                    ->options(RankingList::pluck('title', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatchRecords::route('/'),
            'create' => Pages\CreateMatchRecord::route('/create'),
            'edit' => Pages\EditMatchRecord::route('/{record}/edit'),
        ];
    }

    protected static function setsToWin(Get $get): int
    {
        $list = RankingList::find($get('ranking_list_id'));

        return $list?->getMaxSetsToWin() ?? 2;
    }

    protected static function maxSets(Get $get): int
    {
        $list = RankingList::find($get('ranking_list_id'));

        return $list?->getTotalSetsCount() ?? 3;
    }

    protected static function kFactor(Get $get): int
    {
        $list = RankingList::find($get('ranking_list_id'));

        return (int) ($list?->k_factor ?? 32);
    }

    /**
     * Find the same player/pair on another ranking list.
     */
    protected static function findEquivalentRanking(mixed $rankingId, int $newListId): ?PlayerRanking
    {
        if (! filled($rankingId)) {
            return null;
        }

        $current = PlayerRanking::find($rankingId);

        if (! $current) {
            return null;
        }

        if ((int) $current->ranking_list_id === $newListId) {
            return $current;
        }

        $query = PlayerRanking::query()
            ->where('ranking_list_id', $newListId)
            ->where('user_id', $current->user_id);

        if ($current->player2_id) {
            $query->where('player2_id', $current->player2_id);
        } else {
            $query->whereNull('player2_id');
        }

        return $query->first();
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, elo: ?int}
     */
    protected static function sideDisplay(Get $get, int $side): array
    {
        $fallback = $side === 1 ? 'Side 1' : 'Side 2';
        $rankingId = $get($side === 1 ? 'side1_ranking_id' : 'side2_ranking_id');

        if (! $rankingId) {
            return [
                'name' => $fallback,
                'nickname' => null,
                'title' => $fallback,
                'elo' => null,
            ];
        }

        $ranking = PlayerRanking::with(['user', 'player2'])->find($rankingId);

        if (! $ranking) {
            return [
                'name' => $fallback,
                'nickname' => null,
                'title' => $fallback,
                'elo' => null,
            ];
        }

        $elo = (int) $ranking->elo_rating;

        if ($ranking->team_name) {
            return [
                'name' => $ranking->team_name,
                'nickname' => null,
                'title' => $ranking->team_name,
                'elo' => $elo,
            ];
        }

        if ($ranking->player2_id) {
            $name = trim(($ranking->user?->name ?? 'P1') . ' & ' . ($ranking->player2?->name ?? 'P2'));
            $nicks = collect([$ranking->user?->nickname, $ranking->player2?->nickname])
                ->filter()
                ->map(fn (string $nick): string => '@' . $nick)
                ->implode(' / ');

            return [
                'name' => $name,
                'nickname' => $nicks !== '' ? $nicks : null,
                'title' => $ranking->display_name,
                'elo' => $elo,
            ];
        }

        $userName = $ranking->user?->name ?? $fallback;
        $nickname = $ranking->user?->nickname;

        return [
            'name' => $userName,
            'nickname' => $nickname ? '@' . $nickname : null,
            'title' => $ranking->display_name,
            'elo' => $elo,
        ];
    }
}
