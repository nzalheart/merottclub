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
use Illuminate\Support\Collection;

class MatchRecordResource extends Resource
{
    protected static ?string $model = MatchRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Match Scorekeeper';

    protected static ?string $label = 'Match & Scorekeeper';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof \App\Models\User && $user->isManager();
    }

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
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                $list = $state ? RankingList::find($state) : null;
                                $newListId = $list?->id;

                                if ($list?->type === 'doubles') {
                                    $set('side1_ranking_id', null);
                                    $set('side2_ranking_id', null);

                                    foreach (['side1_player1_id', 'side1_player2_id', 'side2_player1_id', 'side2_player2_id'] as $field) {
                                        $mapped = $newListId
                                            ? static::findEquivalentIndividualRanking($get($field), (int) $newListId)
                                            : null;
                                        $set($field, $mapped?->id);
                                    }
                                } else {
                                    foreach (['side1_player1_id', 'side1_player2_id', 'side2_player1_id', 'side2_player2_id'] as $field) {
                                        $set($field, null);
                                    }

                                    $mapped1 = $newListId
                                        ? static::findEquivalentRanking($get('side1_ranking_id'), (int) $newListId)
                                        : null;
                                    $mapped2 = $newListId
                                        ? static::findEquivalentRanking($get('side2_ranking_id'), (int) $newListId)
                                        : null;

                                    $set('side1_ranking_id', $mapped1?->id);
                                    $set('side2_ranking_id', $mapped2?->id);

                                    if ($mapped1 && $mapped2) {
                                        return;
                                    }
                                }

                                $count = $list?->getDefaultSetsToShow() ?? 2;
                                $set('sets', TableTennisScoring::defaultSetRows($count));
                            }),

                        Forms\Components\Select::make('side1_ranking_id')
                            ->label('Player / Side 1')
                            ->options(fn (Get $get) => static::singlesOptions($get))
                            ->required(fn (Get $get) => ! static::isDoublesList($get))
                            ->visible(fn (Get $get) => ! static::isDoublesList($get))
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('side2_ranking_id')
                            ->label('Player / Side 2')
                            ->options(fn (Get $get) => static::singlesOptions($get))
                            ->required(fn (Get $get) => ! static::isDoublesList($get))
                            ->visible(fn (Get $get) => ! static::isDoublesList($get))
                            ->searchable()
                            ->different('side1_ranking_id')
                            ->live(),

                        Forms\Components\Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Select::make('side1_player1_id')
                                        ->label('Side 1 — Player A')
                                        ->options(fn (Get $get) => static::individualOptions($get))
                                        ->required(fn (Get $get) => static::isDoublesList($get))
                                        ->searchable()
                                        ->dehydrated(false)
                                        ->live(),

                                    Forms\Components\Select::make('side1_player2_id')
                                        ->label('Side 1 — Player B')
                                        ->options(fn (Get $get) => static::individualOptions($get, exclude: [
                                            $get('side1_player1_id'),
                                        ]))
                                        ->required(fn (Get $get) => static::isDoublesList($get))
                                        ->searchable()
                                        ->dehydrated(false)
                                        ->different('side1_player1_id')
                                        ->live(),
                                ])
                                    ->extraAttributes([
                                        'class' => 'space-y-4 border-b border-gray-200 pb-5 dark:border-white/10 md:border-b-0 md:border-r md:pb-0 md:pr-6',
                                    ]),

                                Forms\Components\Group::make([
                                    Forms\Components\Select::make('side2_player1_id')
                                        ->label('Side 2 — Player A')
                                        ->options(fn (Get $get) => static::individualOptions($get, exclude: [
                                            $get('side1_player1_id'),
                                            $get('side1_player2_id'),
                                        ]))
                                        ->required(fn (Get $get) => static::isDoublesList($get))
                                        ->searchable()
                                        ->dehydrated(false)
                                        ->live(),

                                    Forms\Components\Select::make('side2_player2_id')
                                        ->label('Side 2 — Player B')
                                        ->options(fn (Get $get) => static::individualOptions($get, exclude: [
                                            $get('side1_player1_id'),
                                            $get('side1_player2_id'),
                                            $get('side2_player1_id'),
                                        ]))
                                        ->required(fn (Get $get) => static::isDoublesList($get))
                                        ->searchable()
                                        ->dehydrated(false)
                                        ->different('side2_player1_id')
                                        ->live(),
                                ])
                                    ->extraAttributes([
                                        'class' => 'space-y-4 pt-1 md:pt-0 md:pl-6',
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => static::isDoublesList($get)),

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
                                $get('side1_ranking_id') ?? $get('side1_player1_id') ?? 's1a',
                                $get('side1_player2_id') ?? 's1b',
                                $get('side2_ranking_id') ?? $get('side2_player1_id') ?? 's2a',
                                $get('side2_player2_id') ?? 's2b',
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

    /**
     * Resolve doubles player picks into pair ranking IDs on the match record.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rawState  Full form state including dehydrated(false) fields
     * @return array<string, mixed>
     */
    public static function resolveDoublesSides(array $data, array $rawState): array
    {
        $list = RankingList::find($data['ranking_list_id'] ?? null);

        if (! $list) {
            return $data;
        }

        $data['match_type'] = $list->type;

        if ($list->type !== 'doubles') {
            return $data;
        }

        $ids = [
            'side1_player1_id' => $rawState['side1_player1_id'] ?? null,
            'side1_player2_id' => $rawState['side1_player2_id'] ?? null,
            'side2_player1_id' => $rawState['side2_player1_id'] ?? null,
            'side2_player2_id' => $rawState['side2_player2_id'] ?? null,
        ];

        foreach ($ids as $key => $id) {
            if (! filled($id)) {
                throw new \InvalidArgumentException("Missing doubles player selection: {$key}");
            }
        }

        $rankings = PlayerRanking::query()
            ->whereIn('id', array_values($ids))
            ->individuals()
            ->where('ranking_list_id', $list->id)
            ->get()
            ->keyBy('id');

        if ($rankings->count() !== 4) {
            throw new \InvalidArgumentException('All four doubles players must be enrolled as individuals on this list.');
        }

        $userIds = collect($ids)->map(fn ($id) => (int) $rankings[(int) $id]->user_id)->values();

        if ($userIds->unique()->count() !== 4) {
            throw new \InvalidArgumentException('Doubles sides must use four distinct players.');
        }

        $side1Pair = PlayerRanking::findOrCreatePair($list, $userIds[0], $userIds[1]);
        $side2Pair = PlayerRanking::findOrCreatePair($list, $userIds[2], $userIds[3]);

        $data['side1_ranking_id'] = $side1Pair->id;
        $data['side2_ranking_id'] = $side2Pair->id;

        unset(
            $data['side1_player1_id'],
            $data['side1_player2_id'],
            $data['side2_player1_id'],
            $data['side2_player2_id'],
        );

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function doublesPlayerFillFromRecord(MatchRecord $record): array
    {
        $side1 = $record->side1Ranking;
        $side2 = $record->side2Ranking;
        $list = $record->rankingList;

        if (! $list || $list->type !== 'doubles' || ! $side1?->isPair() || ! $side2?->isPair()) {
            return [];
        }

        $ind1a = PlayerRanking::findOrCreateIndividual($list, (int) $side1->user_id);
        $ind1b = PlayerRanking::findOrCreateIndividual($list, (int) $side1->player2_id);
        $ind2a = PlayerRanking::findOrCreateIndividual($list, (int) $side2->user_id);
        $ind2b = PlayerRanking::findOrCreateIndividual($list, (int) $side2->player2_id);

        return [
            'side1_player1_id' => $ind1a->id,
            'side1_player2_id' => $ind1b->id,
            'side2_player1_id' => $ind2a->id,
            'side2_player2_id' => $ind2b->id,
        ];
    }

    protected static function isDoublesList(Get $get): bool
    {
        $list = RankingList::find($get('ranking_list_id'));

        return $list?->type === 'doubles';
    }

    /**
     * @return Collection<int|string, string>
     */
    protected static function singlesOptions(Get $get): Collection
    {
        $listId = $get('ranking_list_id');
        $query = PlayerRanking::query()->with(['user', 'player2']);

        if ($listId) {
            $query->where('ranking_list_id', $listId)->individuals();
        }

        return $query->get()->pluck('display_name', 'id');
    }

    /**
     * @param  list<mixed>  $exclude
     * @return Collection<int|string, string>
     */
    protected static function individualOptions(Get $get, array $exclude = []): Collection
    {
        $listId = $get('ranking_list_id');
        if (! $listId) {
            return collect();
        }

        $excludeIds = collect($exclude)->filter()->map(fn ($id) => (int) $id)->all();

        return PlayerRanking::query()
            ->where('ranking_list_id', $listId)
            ->individuals()
            ->with(['user'])
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->get()
            ->pluck('display_name', 'id');
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

    protected static function findEquivalentIndividualRanking(mixed $rankingId, int $newListId): ?PlayerRanking
    {
        if (! filled($rankingId)) {
            return null;
        }

        $current = PlayerRanking::find($rankingId);

        if (! $current) {
            return null;
        }

        if ((int) $current->ranking_list_id === $newListId && $current->isIndividual()) {
            return $current;
        }

        return PlayerRanking::query()
            ->where('ranking_list_id', $newListId)
            ->where('user_id', $current->user_id)
            ->whereNull('player2_id')
            ->first();
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, elo: ?int}
     */
    protected static function sideDisplay(Get $get, int $side): array
    {
        $fallback = $side === 1 ? 'Side 1' : 'Side 2';

        if (static::isDoublesList($get)) {
            return static::doublesSideDisplay($get, $side, $fallback);
        }

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

        return static::formatRankingSide($ranking, $fallback);
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, elo: ?int}
     */
    protected static function doublesSideDisplay(Get $get, int $side, string $fallback): array
    {
        $p1Key = $side === 1 ? 'side1_player1_id' : 'side2_player1_id';
        $p2Key = $side === 1 ? 'side1_player2_id' : 'side2_player2_id';
        $p1Id = $get($p1Key);
        $p2Id = $get($p2Key);

        if (! filled($p1Id) || ! filled($p2Id)) {
            return [
                'name' => $fallback,
                'nickname' => null,
                'title' => $fallback,
                'elo' => null,
            ];
        }

        $p1 = PlayerRanking::with('user')->find($p1Id);
        $p2 = PlayerRanking::with('user')->find($p2Id);

        if (! $p1 || ! $p2) {
            return [
                'name' => $fallback,
                'nickname' => null,
                'title' => $fallback,
                'elo' => null,
            ];
        }

        $list = RankingList::find($get('ranking_list_id'));
        $pairElo = null;

        if ($list) {
            $pair = PlayerRanking::query()
                ->where('ranking_list_id', $list->id)
                ->where(function ($q) use ($p1, $p2) {
                    $low = min($p1->user_id, $p2->user_id);
                    $high = max($p1->user_id, $p2->user_id);
                    $q->where('user_id', $low)->where('player2_id', $high);
                })
                ->first();

            $pairElo = $pair?->elo_rating;
        }

        $name = trim(($p1->user?->name ?? 'P1') . ' & ' . ($p2->user?->name ?? 'P2'));
        $nicks = collect([$p1->user?->nickname, $p2->user?->nickname])
            ->filter()
            ->map(fn (string $nick): string => '@' . $nick)
            ->implode(' / ');

        $elo = $pairElo ?? (int) round(($p1->elo_rating + $p2->elo_rating) / 2);

        return [
            'name' => $name,
            'nickname' => $nicks !== '' ? $nicks : null,
            'title' => $name,
            'elo' => $elo,
        ];
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, elo: ?int}
     */
    protected static function formatRankingSide(PlayerRanking $ranking, string $fallback): array
    {
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
