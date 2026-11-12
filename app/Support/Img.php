<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Responsive, compressed product photos for slow connections.
 *
 * Uploaded photos are often 1–4 MB. Views ask for the size they actually display:
 *   <img src="{{ Img::url($path, 480) }}" srcset="{{ Img::srcset($path) }}" sizes="...">
 * Smaller WebP copies are made once (on first request, or with `php artisan images:optimize`)
 * in storage/app/public/resized/{width}/…webp and then served as plain static files.
 * Originals are never changed.
 */
class Img
{
    /** The only widths that may be generated (keeps the cache small and the route safe) */
    public const WIDTHS = [160, 320, 480, 720, 960, 1280];

    /** WebP quality: visually close to the original at a fraction of the size */
    public const QUALITY = 72;

    private const SOURCE = '/^(?!resized\/)[A-Za-z0-9][A-Za-z0-9_\-\/\.]*\.(jpe?g|png|webp|gif)$/i';

    /**
     * URL of the photo at (about) this width. Falls back to the original if it can't be resized.
     */
    public static function url(?string $path, int $width = 480): string
    {
        if (! $path) {
            return '';
        }
        if (! self::resizable($path)) {
            return asset('storage/' . ltrim($path, '/'));
        }

        $width = self::nearestWidth($width);
        $resized = self::resizedPath($path, $width);

        // Already made: link the static file directly (no PHP on later requests)
        return Storage::disk('public')->exists($resized)
            ? asset('storage/' . $resized)
            : route('image.resize', ['width' => $width, 'path' => $path]);
    }

    /**
     * "url 320w, url 480w, …" for a srcset attribute.
     * In data-saver mode only the smaller sizes are offered.
     */
    public static function srcset(?string $path, ?array $widths = null): string
    {
        if (! $path || ! self::resizable($path)) {
            return '';
        }

        $widths ??= [320, 480, 720, 960];
        if (DataSaver::on()) {
            $widths = array_values(array_filter($widths, fn ($w) => $w <= 480)) ?: [320];
        }

        return collect($widths)->map(fn ($w) => self::url($path, $w) . ' ' . $w . 'w')->implode(', ');
    }

    public static function resizable(string $path): bool
    {
        return (bool) preg_match(self::SOURCE, $path) && ! str_contains($path, '..');
    }

    public static function nearestWidth(int $width): int
    {
        foreach (self::WIDTHS as $allowed) {
            if ($allowed >= $width) {
                return $allowed;
            }
        }

        return self::WIDTHS[count(self::WIDTHS) - 1];
    }

    public static function resizedPath(string $path, int $width): string
    {
        return 'resized/' . $width . '/' . preg_replace('/\.[a-z0-9]+$/i', '', ltrim($path, '/')) . '.webp';
    }

    /**
     * Create the resized copy (if it doesn't exist yet) and return its path on the public disk.
     *
     * @throws RuntimeException when the original is missing or not an image
     */
    public static function make(string $path, int $width): string
    {
        if (! self::resizable($path) || ! in_array($width, self::WIDTHS, true)) {
            throw new RuntimeException('Not a resizable image.');
        }

        $disk = Storage::disk('public');
        $resized = self::resizedPath($path, $width);
        if ($disk->exists($resized)) {
            return $resized;
        }
        if (! $disk->exists($path)) {
            throw new RuntimeException('Image not found.');
        }

        $sourceFile = $disk->path($path);
        $info = @getimagesize($sourceFile);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            throw new RuntimeException('Not an image.');
        }

        // Large phone photos need room in memory while being decoded
        $needed = (int) ($info[0] * $info[1] * 5) + 64 * 1024 * 1024;
        if (self::bytes(ini_get('memory_limit')) > 0 && self::bytes(ini_get('memory_limit')) < $needed) {
            @ini_set('memory_limit', (string) $needed);
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourceFile),
            IMAGETYPE_PNG => @imagecreatefrompng($sourceFile),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourceFile),
            IMAGETYPE_GIF => @imagecreatefromgif($sourceFile),
        };
        if (! $image) {
            throw new RuntimeException('Image could not be read.');
        }

        $image = self::orient($image, $sourceFile, $info[2]);

        // Never enlarge: a small original keeps its size (but is still compressed)
        $targetWidth = min($width, imagesx($image));
        if ($targetWidth < imagesx($image)) {
            $scaled = imagescale($image, $targetWidth, -1, IMG_BICUBIC);
            imagedestroy($image);
            $image = $scaled;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $data = ob_get_clean();
        imagedestroy($image);

        $disk->put($resized, $data);

        return $resized;
    }

    /**
     * Phone photos are often stored sideways with an EXIF "rotate me" flag.
     */
    private static function orient(\GdImage $image, string $file, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private static function bytes(string|false $value): int
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
