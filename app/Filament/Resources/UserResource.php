<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Club Management';

    protected static ?string $label = 'Member / User';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Member Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Full Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nickname')
                            ->required()
                            ->unique(User::class, 'nickname', ignoreRecord: true)
                            ->label('Unique Nickname')
                            ->maxLength(255)
                            ->placeholder('e.g., dragon'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options([
                                'member' => 'Member (Player)',
                                'manager' => 'Manager (Scorekeeper)',
                                'admin' => 'Admin (Super User)',
                            ])
                            ->required()
                            ->default('member'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->label('Phone Number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->label('Password (Leave empty to keep unchanged)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->label('Nickname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'member',
                        'warning' => 'manager',
                        'danger' => 'admin',
                    ]),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'member' => 'Member',
                        'manager' => 'Manager',
                        'admin' => 'Admin',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
