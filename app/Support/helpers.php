<?php

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value, bool $withPrefix = true): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }
}

if (! function_exists('rupiah_decimal')) {
    function rupiah_decimal(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 2, ',', '.');
    }
}

if (! function_exists('asset_v')) {
    /**
     * URL aset dengan query "?v=" dari waktu modifikasi file, agar browser
     * selalu mengambil versi terbaru setelah CSS/JS diperbarui.
     */
    function asset_v(string $path): string
    {
        $path = ltrim($path, '/');
        $file = public_path($path);

        $version = is_file($file) ? filemtime($file) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}
