<?php

namespace App\Support;

use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AvatarResizer
{
    public const MAX_EDGE = 500;

    public const MAX_BYTES = 200 * 1024;

    public function store(UploadedFile $file, User $user): string
    {
        $binary = file_get_contents($file->getRealPath());
        $source = $binary === false ? false : @imagecreatefromstring($binary);

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages([
                'avatar' => 'That file is not a readable image.',
            ]);
        }

        $jpeg = $this->fit($source);
        imagedestroy($source);

        $path = 'avatars/'.$user->id.'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        $previous = $user->avatar_url;
        if (is_string($previous) && $previous !== '' && $previous !== $path && ! str_starts_with($previous, 'http')) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }

    private function fit(GdImage $source): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $edge = min(self::MAX_EDGE, max($width, $height));
        $encoded = '';

        while ($edge >= 64) {
            $scale = $edge / max($width, $height);
            $canvas = $this->resample(
                $source,
                max(1, (int) round($width * $scale)),
                max(1, (int) round($height * $scale)),
            );

            for ($quality = 85; $quality >= 40; $quality -= 10) {
                $encoded = $this->jpeg($canvas, $quality);

                if (strlen($encoded) <= self::MAX_BYTES) {
                    imagedestroy($canvas);

                    return $encoded;
                }
            }

            imagedestroy($canvas);
            $edge = (int) floor($edge * 0.8);
        }

        return $encoded;
    }

    private function resample(GdImage $source, int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        return $canvas;
    }

    private function jpeg(GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }
}
