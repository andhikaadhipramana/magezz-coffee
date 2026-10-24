<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;

class MenuImage
{
    /**
     * Lebar varian yang dibuat. Tidak pernah memperbesar dari ukuran asli.
     *
     * @var array<int, int>
     */
    public const WIDTHS = [320, 640, 960];

    public const QUALITY = 82;

    /**
     * Path varian relatif terhadap disk "menus".
     */
    public static function variantPath(string $path, int $width): string
    {
        $info = pathinfo($path);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';

        return $directory.$info['filename'].'-'.$width.'.webp';
    }

    /**
     * Buat varian WebP untuk satu berkas di disk "menus".
     *
     * @return array<int, array{width: int, path: string, bytes: int}>
     */
    public static function generate(string $path, bool $force = false, int $quality = self::QUALITY): array
    {
        $disk = Storage::disk('menus');

        if (! $disk->exists($path)) {
            return [];
        }

        $source = self::load($disk->path($path));

        if (! $source instanceof GdImage) {
            return [];
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $results = [];

        foreach (self::WIDTHS as $width) {
            if ($width > $sourceWidth) {
                continue;
            }

            $variant = self::variantPath($path, $width);

            if (! $force && $disk->exists($variant)) {
                $results[] = [
                    'width' => $width,
                    'path' => $variant,
                    'bytes' => (int) $disk->size($variant),
                ];

                continue;
            }

            $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
            $canvas = imagecreatetruecolor($width, $height);

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            imagealphablending($canvas, true);

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            $target = $disk->path($variant);

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            imagewebp($canvas, $target, max(1, min(100, $quality)));
            imagedestroy($canvas);

            $results[] = [
                'width' => $width,
                'path' => $variant,
                'bytes' => is_file($target) ? (int) filesize($target) : 0,
            ];
        }

        imagedestroy($source);

        return $results;
    }

    /**
     * Hapus semua varian milik sebuah berkas asli.
     */
    public static function deleteVariants(string $path): void
    {
        $disk = Storage::disk('menus');

        foreach (self::WIDTHS as $width) {
            $variant = self::variantPath($path, $width);

            if ($disk->exists($variant)) {
                $disk->delete($variant);
            }
        }
    }

    /**
     * Atribut srcset untuk berkas asli, atau null bila varian belum tersedia.
     */
    public static function srcset(string $path): ?string
    {
        $disk = Storage::disk('menus');
        $items = [];

        foreach (self::WIDTHS as $width) {
            $variant = self::variantPath($path, $width);

            if ($disk->exists($variant)) {
                $items[] = asset_v('uploads/menus/'.$variant).' '.$width.'w';
            }
        }

        return $items === [] ? null : implode(', ', $items);
    }

    /**
     * Muat berkas gambar menjadi resource GD, sekaligus membetulkan orientasi EXIF.
     */
    private static function load(string $absolutePath): GdImage|false
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $info = @getimagesize($absolutePath);

        if (! $info) {
            return false;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absolutePath),
            default => false,
        };

        if (! $image instanceof GdImage) {
            return false;
        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($absolutePath);
            $orientation = (int) ($exif['Orientation'] ?? 1);

            $angle = match ($orientation) {
                3, 4 => 180,
                5, 6 => -90,
                7, 8 => 90,
                default => 0,
            };

            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);

                if ($rotated !== false) {
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
        }

        return $image;
    }
}
