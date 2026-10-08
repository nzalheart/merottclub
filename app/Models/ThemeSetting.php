<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ThemeSetting extends Model
{
    /** Nepal flag crimson */
    public const DEFAULT_PRIMARY = '#DC143C';

    /** Nepal flag blue */
    public const DEFAULT_SECONDARY = '#003893';

    protected $fillable = [
        'primary_color',
        'secondary_color',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('theme_settings')) {
            return static::makeDefaults();
        }

        $theme = static::query()->first();

        if (! $theme) {
            $theme = static::query()->create([
                'primary_color' => self::DEFAULT_PRIMARY,
                'secondary_color' => self::DEFAULT_SECONDARY,
            ]);
        }

        return $theme;
    }

    public static function makeDefaults(): self
    {
        $theme = new static([
            'primary_color' => self::DEFAULT_PRIMARY,
            'secondary_color' => self::DEFAULT_SECONDARY,
        ]);
        $theme->exists = false;

        return $theme;
    }

    public static function primaryHex(): string
    {
        try {
            return static::current()->primary_color ?: self::DEFAULT_PRIMARY;
        } catch (\Throwable) {
            return self::DEFAULT_PRIMARY;
        }
    }

    public static function secondaryHex(): string
    {
        try {
            return static::current()->secondary_color ?: self::DEFAULT_SECONDARY;
        } catch (\Throwable) {
            return self::DEFAULT_SECONDARY;
        }
    }

    /**
     * Persist safely even when the singleton row was fabricated before migrate.
     */
    public function saveColors(string $primary, string $secondary): self
    {
        $primary = strtoupper($primary);
        $secondary = strtoupper($secondary);

        if (! Schema::hasTable('theme_settings')) {
            $this->primary_color = $primary;
            $this->secondary_color = $secondary;

            return $this;
        }

        $theme = static::query()->first();

        if (! $theme) {
            return static::query()->create([
                'primary_color' => $primary,
                'secondary_color' => $secondary,
            ]);
        }

        $theme->update([
            'primary_color' => $primary,
            'secondary_color' => $secondary,
        ]);

        return $theme->fresh();
    }
}
