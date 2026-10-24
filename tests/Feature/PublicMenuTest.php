<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(string $code = 'KAT-01', string $name = 'Kopi Espresso Based'): Category
    {
        return Category::create(['code' => $code, 'name' => $name, 'is_active' => true]);
    }

    private function menu(Category $category, array $attributes = []): Menu
    {
        return Menu::create(array_merge([
            'category_id' => $category->id,
            'code' => 'MNU-'.uniqid(),
            'name' => 'Espresso',
            'price' => 18000,
            'is_signature' => false,
            'is_active' => true,
        ], $attributes));
    }

    public function test_customer_dapat_membuka_katalog_tanpa_login(): void
    {
        $this->kategori();
        Setting::set('outlet_name', 'Magezz Coffee');

        $this->get('/')
            ->assertOk()
            ->assertSee('Magezz Coffee')
            ->assertSee('Login Staf');
    }

    public function test_dashboard_tetap_wajib_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_staf_dapat_login_dan_diarahkan_ke_dashboard(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->post('/login', [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($manager);
    }

    public function test_menu_nonaktif_tidak_muncul(): void
    {
        $category = $this->kategori();
        $this->menu($category, ['name' => 'Menu Tampil']);
        $this->menu($category, ['name' => 'Menu Tersembunyi', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Menu Tampil')
            ->assertDontSee('Menu Tersembunyi');
    }

    public function test_menu_dari_kategori_nonaktif_tidak_muncul(): void
    {
        $category = Category::create(['code' => 'KAT-OFF', 'name' => 'Kategori Nonaktif', 'is_active' => false]);
        $this->menu($category, ['name' => 'Menu Kategori Mati']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Menu Kategori Mati')
            ->assertDontSee('Kategori Nonaktif');
    }

    public function test_katalog_tidak_lagi_menampilkan_badge_habis(): void
    {
        $category = $this->kategori();
        $this->menu($category, ['name' => 'Kopi Susu']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertDontSee('Habis');
    }

    public function test_pencarian_menyaring_menu(): void
    {
        $category = $this->kategori();
        $this->menu($category, ['name' => 'Caramel Macchiato']);
        $this->menu($category, ['name' => 'Nasi Goreng']);

        $this->get('/?q=Caramel')
            ->assertOk()
            ->assertSee('Caramel Macchiato')
            ->assertDontSee('Nasi Goreng');
    }

    public function test_filter_kategori_menyaring_dan_mempertahankan_pencarian(): void
    {
        $kopi = $this->kategori('KAT-01', 'Kopi');
        $makanan = $this->kategori('KAT-04', 'Makanan');
        $this->menu($kopi, ['name' => 'Kopi Susu']);
        $this->menu($makanan, ['name' => 'Kopi Jadul']);

        $this->get('/?q=Kopi&category_id='.$kopi->id)
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertDontSee('Kopi Jadul')
            ->assertSee('value="Kopi"', false);
    }

    public function test_badge_signature_tampil(): void
    {
        $category = $this->kategori();
        $this->menu($category, ['name' => 'Kopi Susu Gula Aren', 'is_signature' => true]);

        $this->get('/')->assertOk()->assertSee('Signature');
    }

    public function test_kartu_signature_di_hero_memakai_foto_menu_signature(): void
    {
        Storage::fake('menus');
        Storage::disk('menus')->put('signature.jpg', 'gambar-signature');

        $category = $this->kategori();
        $this->menu($category, [
            'name' => 'Kopi Susu Gula Aren',
            'is_signature' => true,
            'image_path' => 'signature.jpg',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-signature-photo', false)
            ->assertSee('alt="Foto Kopi Susu Gula Aren"', false);
    }

    public function test_kartu_signature_di_hero_bisa_langsung_masuk_keranjang(): void
    {
        $category = $this->kategori();
        $menu = $this->menu($category, [
            'name' => 'Kopi Susu Gula Aren',
            'price' => 24000,
            'is_signature' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('public.cart.add').'"', false)
            ->assertSee('name="menu_id" value="'.$menu->id.'"', false)
            ->assertSee('name="redirect" value="cart"', false)
            ->assertSee('aria-label="Tambah Kopi Susu Gula Aren ke keranjang"', false);
    }

    public function test_foto_menu_tampil_di_katalog_publik(): void
    {
        Storage::fake('menus');
        Storage::disk('menus')->put('espresso.jpg', 'gambar-demo');

        $category = $this->kategori();
        $this->menu($category, ['name' => 'Espresso', 'image_path' => 'espresso.jpg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('alt="Foto Espresso"', false)
            ->assertSee(Storage::disk('menus')->url('espresso.jpg'), false);
    }

    public function test_menu_tanpa_foto_tetap_memakai_ikon_kategori(): void
    {
        $category = $this->kategori();
        $this->menu($category, ['name' => 'Espresso Tanpa Foto']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Espresso Tanpa Foto')
            ->assertDontSee('alt="Foto Espresso Tanpa Foto"', false)
            ->assertSee('bi bi-cup-hot-fill', false);
    }

    public function test_file_foto_yang_hilang_tidak_menampilkan_gambar_rusak(): void
    {
        Storage::fake('menus');

        $category = $this->kategori();
        $this->menu($category, ['name' => 'Kopi Hilang', 'image_path' => 'hilang.jpg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kopi Hilang')
            ->assertDontSee('alt="Foto Kopi Hilang"', false);
    }
}
