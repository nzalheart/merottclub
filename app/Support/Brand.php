<?php

namespace App\Support;

class Brand
{
    public static function name(): string
    {
        $name = config('app.name');

        return is_string($name) && $name !== '' ? $name : 'Table Tennis Club';
    }

    public static function logoUrl(): ?string
    {
        $logo = config('app.logo');

        if (! is_string($logo) || trim($logo) === '') {
            return null;
        }

        $logo = trim($logo);

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset(ltrim($logo, '/'));
    }
}
