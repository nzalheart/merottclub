<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingListResource\Pages;
use App\Models\RankingList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RankingListResource extends Resource
{
    protected static ?string $model = RankingList::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Club Management';

    protected static ?string $label = 'Ranking List';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ranking List Configuration')
                    ->description('Admins can configure dynamic ranking lists for Singles or Doubles with custom match rules.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->label('Ranking List Title')
                            ->placeholder('e.g., Premier Singles Best of 5')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'singles' => 'Singles (1 v 1)',
                                'doubles' => 'Doubles (2 v 2 Pairs)',
                            ])
                            ->required()
                            ->default('singles'),
                        Forms\Components\Select::make('format')
                            ->options([
                                'best_of_3' => 'Best of 3 Sets (First to win 2)',
                                'best_of_5' => 'Best of 5 Sets (First to win 3)',
                                'best_of_7' => 'Best of 7 Sets (First to win 4)',
                            ])
                            ->required()
                            ->default('best_of_5'),
                        Forms\Components\TextInput::make('initial_elo')
                            ->label('Starting ELO Rating')
                            ->required()
                            ->numeric()
                            ->default(1200),
                        Forms\Components\TextInput::make('k_factor')
                            ->label('ELO K-Factor Multiplier')
                            ->helperText('Standard is 32. Higher values increase rating gain/loss per match.')
                            ->required()
                            ->numeric()
                            ->default(32)
                            ->visible(fn () => auth()->check() && auth()->user()?->isAdmin()),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active for New Matches')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'info' => 'singles',
                        'warning' => 'doubles',
                    ]),
                Tables\Columns\BadgeColumn::make('format')
                    ->colors([
                        'success' => 'best_of_3',
                        'primary' => 'best_of_5',
                        'danger' => 'best_of_7',
                    ]),
                Tables\Columns\TextColumn::make('initial_elo')
                    ->label('Start ELO')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('k_factor')
                    ->label('K-Factor')
                    ->numeric()
                    ->sortable()
                    ->visible(fn () => auth()->check() && auth()->user()?->isAdmin()),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rankings_count')
                    ->counts('rankings')
                    ->label('Players Registered'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'singles' => 'Singles',
                        'doubles' => 'Doubles',
                    ]),
                Tables\Filters\SelectFilter::make('format')
                    ->options([
                        'best_of_3' => 'Best of 3',
                        'best_of_5' => 'Best of 5',
                        'best_of_7' => 'Best of 7',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRankingLists::route('/'),
            'create' => Pages\CreateRankingList::route('/create'),
            'edit' => Pages\EditRankingList::route('/{record}/edit'),
        ];
    }
}
