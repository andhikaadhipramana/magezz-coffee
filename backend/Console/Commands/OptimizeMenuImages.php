<?php

namespace App\Console\Commands;

use App\Support\MenuImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeMenuImages extends Command
{
    protected $signature = 'images:optimize
                            {--force : Timpa varian yang sudah ada}
                            {--dry-run : Tampilkan rencana tanpa menulis berkas}
                            {--quality=82 : Kualitas WebP (1-100)}';

    protected $description = 'Membuat varian WebP (320/640/960 px) untuk foto menu';

    public function handle(): int
    {
        $disk = Storage::disk('menus');

        $files = collect($disk->allFiles())
            ->filter(fn (string $file): bool => (bool) preg_match('/\.(jpe?g|png|webp)$/i', $file))
            ->reject(fn (string $file): bool => (bool) preg_match('/-\d+\.webp$/i', $file))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->warn('Tidak ada gambar di public/uploads/menus.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $quality = max(1, min(100, (int) $this->option('quality')));

        $this->info(
            ($dry ? '[dry-run] ' : '').
            "Memproses {$files->count()} gambar (kualitas WebP {$quality})."
        );

        $rows = [];
        $before = 0;
        $after = 0;

        foreach ($files as $file) {
            $originalBytes = (int) $disk->size($file);
            $before += $originalBytes;

            if ($dry) {
                $info = @getimagesize($disk->path($file));
                $sourceWidth = (int) ($info[0] ?? 0);
                $count = collect(MenuImage::WIDTHS)->filter(fn (int $w): bool => $w <= $sourceWidth)->count();

                $rows[] = [basename($file), $this->human($originalBytes), '—', $count.' varian'];

                continue;
            }

            $variants = MenuImage::generate($file, $force, $quality);
            $variantBytes = (int) array_sum(array_column($variants, 'bytes'));
            $after += $variantBytes;

            $rows[] = [
                basename($file),
                $this->human($originalBytes),
                $this->human($variantBytes),
                count($variants).' varian',
            ];
        }

        $this->table(['Berkas', 'Asli', 'Total varian', 'Status'], $rows);

        if ($dry) {
            $this->line('Jalankan tanpa --dry-run untuk benar-benar membuat varian.');

            return self::SUCCESS;
        }

        $this->info('Selesai. Asli: '.$this->human($before).' → total varian: '.$this->human($after));
        $this->line('Catatan: setiap pengunjung hanya mengunduh satu varian sesuai lebar layar.');

        return self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.').' MB';
        }

        return number_format($bytes / 1024, 0, ',', '.').' KB';
    }
}
