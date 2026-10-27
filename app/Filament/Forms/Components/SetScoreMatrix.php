<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

class SetScoreMatrix extends Field
{
    protected string $view = 'filament.forms.components.set-score-matrix';

    /**
     * @var array{name: string, nickname: ?string, title: string, elo?: ?int}|Closure
     */
    protected array | Closure $side1 = [
        'name' => 'Side 1',
        'nickname' => null,
        'title' => 'Side 1',
        'elo' => null,
    ];

    /**
     * @var array{name: string, nickname: ?string, title: string, elo?: ?int}|Closure
     */
    protected array | Closure $side2 = [
        'name' => 'Side 2',
        'nickname' => null,
        'title' => 'Side 2',
        'elo' => null,
    ];

    protected int | Closure $minSets = 2;

    protected int | Closure $maxSets = 5;

    protected int | Closure $kFactor = 32;

    protected string | int | null | Closure $matrixKey = null;

    /**
     * @param  array{name: string, nickname: ?string, title?: string, elo?: ?int}|Closure  $side
     */
    public function side1(array | Closure $side): static
    {
        $this->side1 = $side;

        return $this;
    }

    /**
     * @param  array{name: string, nickname: ?string, title?: string, elo?: ?int}|Closure  $side
     */
    public function side2(array | Closure $side): static
    {
        $this->side2 = $side;

        return $this;
    }

    public function minSets(int | Closure $count): static
    {
        $this->minSets = $count;

        return $this;
    }

    public function maxSets(int | Closure $count): static
    {
        $this->maxSets = $count;

        return $this;
    }

    public function kFactor(int | Closure $kFactor): static
    {
        $this->kFactor = $kFactor;

        return $this;
    }

    public function matrixKey(string | int | null | Closure $key): static
    {
        $this->matrixKey = $key;

        return $this;
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, short_name: string, elo: ?int}
     */
    public function getSide1(): array
    {
        return $this->normalizeSide($this->evaluate($this->side1), 'Side 1');
    }

    /**
     * @return array{name: string, nickname: ?string, title: string, short_name: string, elo: ?int}
     */
    public function getSide2(): array
    {
        return $this->normalizeSide($this->evaluate($this->side2), 'Side 2');
    }

    public function getMinSets(): int
    {
        return (int) $this->evaluate($this->minSets);
    }

    public function getMaxSets(): int
    {
        return (int) $this->evaluate($this->maxSets);
    }

    public function getKFactor(): int
    {
        return (int) $this->evaluate($this->kFactor);
    }

    public function getMatrixKey(): string
    {
        return (string) ($this->evaluate($this->matrixKey) ?? 'default');
    }

    /**
     * @param  mixed  $side
     * @return array{name: string, nickname: ?string, title: string, short_name: string, elo: ?int, avatars: list<array{url: ?string, initials: string, name: string}>}
     */
    protected function normalizeSide(mixed $side, string $fallback): array
    {
        if (! is_array($side)) {
            $side = ['name' => $fallback, 'nickname' => null, 'title' => $fallback, 'elo' => null, 'avatars' => []];
        }

        $name = (string) ($side['name'] ?? $fallback);
        $nickname = $side['nickname'] ?? null;
        $nickname = filled($nickname) ? (string) $nickname : null;
        $title = (string) ($side['title'] ?? $name);
        $elo = $side['elo'] ?? null;
        $avatars = is_array($side['avatars'] ?? null) ? array_values($side['avatars']) : [];

        return [
            'name' => $name,
            'nickname' => $nickname,
            'title' => $title,
            'short_name' => Str::limit($name, 12, '...'),
            'elo' => $elo !== null ? (int) $elo : null,
            'avatars' => $avatars,
        ];
    }
}
