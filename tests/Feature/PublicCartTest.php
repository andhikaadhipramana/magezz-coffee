<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Services\PublicCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCartTest extends TestCase
{
    use RefreshDatabase;

    private PublicCartService $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cart = app(PublicCartService::class);
    }

    private function menu(array $menuAttributes = []): Menu
    {
        $category = Category::create(['code' => 'KAT-CART-'.uniqid(), 'name' => 'Kopi', 'is_active' => true]);

        return Menu::create(array_merge([
            'category_id' => $category->id,
            'code' => 'MNU-CART-'.uniqid(),
            'name' => 'Kopi Susu',
            'price' => 20000,
            'is_active' => true,
        ], $menuAttributes));
    }

    public function test_tamu_dapat_menambahkan_menu_ke_keranjang(): void
    {
        $menu = $this->menu();

        // fallback tanpa JS: tetap di halaman sebelumnya, tidak dipaksa pindah ke keranjang
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('success');

        $items = $this->cart->all();
        $this->assertArrayHasKey($menu->id, $items);
        $this->assertSame(2, $items[$menu->id]['quantity']);
        $this->assertSame(40000.0, $items[$menu->id]['subtotal']);
        $this->assertSame(40000.0, $this->cart->total());

        $this->get(route('public.cart.index'))
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertSee('Keranjang Pesanan');
    }

    public function test_tambah_dengan_redirect_langsung_ke_keranjang(): void
    {
        $menu = $this->menu([
            'name' => 'Kopi Susu Gula Aren',
            'price' => 24000,
        ]);

        $this->post(route('public.cart.add'), [
            'menu_id' => $menu->id,
            'quantity' => 1,
            'redirect' => 'cart',
        ])
            ->assertRedirect(route('public.cart.index'))
            ->assertSessionHas('success');

        $items = $this->cart->all();
        $this->assertArrayHasKey($menu->id, $items);
        $this->assertSame(1, $items[$menu->id]['quantity']);
    }

    public function test_tambah_via_ajax_tetap_di_halaman_katalog(): void
    {
        $menu = $this->menu();

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 2], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->assertOk()->assertJson([
            'success' => true,
            'count' => 2,
        ]);

        $this->assertSame(2, $this->cart->count());
    }

    public function test_ajax_menu_nonaktif_mengembalikan_gagal(): void
    {
        $menu = $this->menu(['is_active' => false]);

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->assertOk()->assertJson([
            'success' => false,
        ]);

        $this->assertTrue($this->cart->isEmpty());
    }

    public function test_endpoint_jumlah_keranjang(): void
    {
        $menu = $this->menu();

        $this->get(route('public.cart.count'))->assertOk()->assertJson(['count' => 0]);

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 3]);

        $this->get(route('public.cart.count'))
            ->assertOk()
            ->assertJson([
                'count' => 3,
                'subtotal' => 60000,
                'tax_amount' => 6000,
                'total' => 66000,
            ]);
    }

    public function test_item_yang_sama_dijumlahkan(): void
    {
        $menu = $this->menu();

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 1])->assertRedirect();
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 1])->assertRedirect();

        $this->assertSame(2, $this->cart->all()[$menu->id]['quantity']);
        $this->assertSame(1, count($this->cart->all()));
    }

    public function test_menu_nonaktif_ditolak(): void
    {
        $menu = $this->menu(['is_active' => false]);

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($this->cart->isEmpty());
    }

    public function test_semua_menu_aktif_selalu_bisa_ditambahkan(): void
    {
        $menu = $this->menu();

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($this->cart->isEmpty());
    }

    public function test_update_kuantitas_memperbarui_subtotal(): void
    {
        $menu = $this->menu();
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 1]);

        $this->post(route('public.cart.update'), ['menu_id' => $menu->id, 'quantity' => 3])
            ->assertRedirect()
            ->assertSessionHas('success');

        $item = $this->cart->all()[$menu->id];
        $this->assertSame(3, $item['quantity']);
        $this->assertSame(60000.0, $item['subtotal']);
    }

    public function test_update_ke_nol_menghapus_item(): void
    {
        $menu = $this->menu();
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 1]);

        $this->post(route('public.cart.update'), ['menu_id' => $menu->id, 'quantity' => 0])->assertRedirect();

        $this->assertTrue($this->cart->isEmpty());
    }

    public function test_hapus_item_dan_kosongkan_keranjang(): void
    {
        $menuA = $this->menu(['name' => 'Menu A']);
        $menuB = $this->menu(['name' => 'Menu B']);

        $this->post(route('public.cart.add'), ['menu_id' => $menuA->id]);
        $this->post(route('public.cart.add'), ['menu_id' => $menuB->id]);

        $this->post(route('public.cart.remove'), ['menu_id' => $menuA->id])->assertRedirect();
        $this->assertSame(1, count($this->cart->all()));

        $this->post(route('public.cart.clear'))->assertRedirect();
        $this->assertTrue($this->cart->isEmpty());
    }

    public function test_tombol_keranjang_menampilkan_jumlah_item(): void
    {
        $menu = $this->menu();

        $this->get(route('public.menu'))->assertOk()->assertDontSee('id="cartBubble"', false);

        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 2]);

        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('id="cartBubble"', false)
            // gelembung menampilkan total porsi, bukan jumlah jenis menu
            ->assertSee('id="cartBubble">2</span>', false)
            ->assertSee('Keranjang');
    }

    public function test_tombol_keranjang_melayang_di_kanan_bawah(): void
    {
        $menu = $this->menu();
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => 1]);

        // tampil di katalog sebagai tombol melayang
        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('id="cartButton"', false)
            ->assertSee('bi-basket2-fill', false);

        // tidak tampil di halaman keranjang (tak perlu tombol menuju dirinya sendiri)
        $this->get(route('public.cart.index'))
            ->assertOk()
            ->assertDontSee('id="cartButton"', false);
    }
}
