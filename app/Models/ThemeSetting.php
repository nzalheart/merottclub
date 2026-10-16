<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ThemeSetting extends Model
{
    /** Nepal flag crimson */
    public const DEFAULT_PRIMARY = '#DC143C';

    /** Nepal flag blue */
    public const DEFAULT_SECONDARY = '#003893';

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'logo_path',
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

    /**
     * Save brand colors and the uploaded logo path.
     * A null logo clears the upload and removes the stored file.
     */
    public function saveBrand(string $primary, string $secondary, ?string $logoPath): self
    {
        $primary = strtoupper($primary);
        $secondary = strtoupper($secondary);
        $logoPath = is_string($logoPath) && $logoPath !== '' ? $logoPath : null;

        if (! Schema::hasTable('theme_settings')) {
            $this->primary_color = $primary;
            $this->secondary_color = $secondary;
            $this->logo_path = $logoPath;

            return $this;
        }

        $theme = static::query()->first();

        if (! $theme) {
            return static::query()->create([
                'primary_color' => $primary,
                'secondary_color' => $secondary,
                'logo_path' => $logoPath,
            ]);
        }

        $previousLogo = $theme->logo_path;

        $theme->update([
            'primary_color' => $primary,
            'secondary_color' => $secondary,
            'logo_path' => $logoPath,
        ]);

        if (is_string($previousLogo) && $previousLogo !== '' && $previousLogo !== $logoPath) {
            Storage::disk('public')->delete($previousLogo);
        }

        return $theme->fresh();
    }

    public function logoUrl(): ?string
    {
        $path = $this->logo_path;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
