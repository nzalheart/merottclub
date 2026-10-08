<?php

namespace App\Filament\Pages;

use App\Models\ThemeSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageTheme extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static string $view = 'filament.pages.manage-theme';

    protected static ?string $navigationLabel = 'Theme Colors';

    protected static ?string $title = 'Theme Colors';

    protected static ?string $navigationGroup = 'Club Management';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $theme = ThemeSetting::current();

        $this->form->fill([
            'primary_color' => $theme->primary_color ?: ThemeSetting::DEFAULT_PRIMARY,
            'secondary_color' => $theme->secondary_color ?: ThemeSetting::DEFAULT_SECONDARY,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brand palette')
                    ->description('Only two brand colors are configurable. The public site background is always white. Defaults match the Nepal national flag.')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary color')
                            ->helperText('Buttons, active pills, wins, and strong highlights. Default: Nepal crimson.')
                            ->required()
                            ->live(),
                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label('Secondary color')
                            ->helperText('Nav accents, alternate pills, and supporting highlights. Default: Nepal blue.')
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ThemeSetting::current()->saveColors(
            $data['primary_color'],
            $data['secondary_color'],
        );

        Notification::make()
            ->title('Theme colors saved')
            ->body('Public pages will use the new primary and secondary colors.')
            ->success()
            ->send();
    }

    public function resetToNepalDefaults(): void
    {
        $this->form->fill([
            'primary_color' => ThemeSetting::DEFAULT_PRIMARY,
            'secondary_color' => ThemeSetting::DEFAULT_SECONDARY,
        ]);

        ThemeSetting::current()->saveColors(
            ThemeSetting::DEFAULT_PRIMARY,
            ThemeSetting::DEFAULT_SECONDARY,
        );

        Notification::make()
            ->title('Reset to Nepal flag defaults')
            ->success()
            ->send();
    }
}
