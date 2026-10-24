<?php

namespace Tests\Feature;

use App\Support\MenuImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('menus');
    }

    public function test_membuat_varian_webp_sesuai_lebar(): void
    {
        $this->buatGambarContoh('contoh.jpg', 800, 600);

        $variants = MenuImage::generate('contoh.jpg');

        $this->assertCount(2, $variants, 'Hanya lebar 320 dan 640 yang lebih kecil dari sumber.');
        $this->assertSame([320, 640], array_column($variants, 'width'));

        foreach ($variants as $variant) {
            Storage::disk('menus')->assertExists($variant['path']);
            $this->assertGreaterThan(0, $variant['bytes']);
        }
    }

    public function test_tidak_memperbesar_gambar_kecil(): void
    {
        $this->buatGambarContoh('kecil.jpg', 300, 300);

        $this->assertSame([], MenuImage::generate('kecil.jpg'));
    }

    public function test_srcset_berisi_varian_yang_tersedia(): void
    {
        $this->buatGambarContoh('contoh.jpg', 800, 600);
        MenuImage::generate('contoh.jpg');

        $srcset = MenuImage::srcset('contoh.jpg');

        $this->assertNotNull($srcset);
        $this->assertStringContainsString('contoh-320.webp', $srcset);
        $this->assertStringContainsString('320w', $srcset);
        $this->assertStringContainsString('640w', $srcset);
    }

    public function test_menghapus_varian_milik_sebuah_berkas(): void
    {
        $this->buatGambarContoh('contoh.jpg', 800, 600);
        MenuImage::generate('contoh.jpg');

        MenuImage::deleteVariants('contoh.jpg');

        Storage::disk('menus')->assertMissing('contoh-320.webp');
        Storage::disk('menus')->assertMissing('contoh-640.webp');
        Storage::disk('menus')->assertExists('contoh.jpg');
    }

    private function buatGambarContoh(string $name, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 120, 72, 40));

        $path = Storage::disk('menus')->path($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}
