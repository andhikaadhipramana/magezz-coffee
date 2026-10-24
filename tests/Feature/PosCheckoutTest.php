<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // fokus tes pada alur checkout, bukan perhitungan pajak
        Setting::set('tax_enabled', '0');
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    private function menu(): Menu
    {
        $category = Category::create([
            'code' => 'KAT-TEST',
            'name' => 'Kopi',
            'is_active' => true,
        ]);

        return Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-TEST',
            'name' => 'Kopi Susu Test',
            'price' => 20000,
            'is_active' => true,
        ]);
    }

    private function isiKeranjang(User $kasir, Menu $menu, int $qty): void
    {
        $this->actingAs($kasir)->post(route('pos.add'), [
            'menu_id' => $menu->id,
            'quantity' => $qty,
        ])->assertRedirect();
    }

    public function test_checkout_berhasil_membuat_transaksi(): void
    {
        $kasir = $this->kasir();
        $menu = $this->menu();

        $this->isiKeranjang($kasir, $menu, 2);

        $response = $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => 'cash',
            'paid_amount' => 100000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(1, Transaction::count());

        $transaction = Transaction::first();
        $this->assertMatchesRegularExpression('/^MGC-\d{8}-[A-Z0-9]{6}$/', $transaction->code);
        $this->assertSame(40000.0, (float) $transaction->total);
        $this->assertSame(60000.0, (float) $transaction->change_amount);
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->status);
        $this->assertSame(1, $transaction->items()->count());
        $this->assertSame('Kopi Susu Test', $transaction->items()->first()->menu_name);

        // keranjang kosong setelah checkout
        $this->actingAs($kasir)->get(route('pos.index'))
            ->assertOk()
            ->assertViewHas('cart', fn ($cart) => empty($cart));
    }

    public function test_checkout_tunai_kurang_ditolak_dan_keranjang_tetap_utuh(): void
    {
        $kasir = $this->kasir();
        $menu = $this->menu();

        $this->isiKeranjang($kasir, $menu, 5);

        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => 'cash',
            'paid_amount' => 1000,
        ])->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());

        $response = $this->actingAs($kasir)->get(route('pos.index'));
        $response->assertOk();
        $this->assertNotEmpty($response->viewData('cart'));
    }

    public function test_checkout_non_tunai_mencatat_pembayaran_penuh(): void
    {
        $kasir = $this->kasir();
        $menu = $this->menu();

        $this->isiKeranjang($kasir, $menu, 1);

        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => 'qris',
        ])->assertSessionHas('success');

        $transaction = Transaction::first();
        $this->assertSame(20000.0, (float) $transaction->total);
        $this->assertSame(20000.0, (float) $transaction->paid_amount);
        $this->assertSame(0.0, (float) $transaction->change_amount);
    }

    public function test_checkout_dengan_diskon_persen(): void
    {
        $kasir = $this->kasir();
        $menu = $this->menu();

        $this->isiKeranjang($kasir, $menu, 1);

        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'discount_type' => 'percent',
            'discount_value' => 10,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
        ])->assertSessionHas('success');

        $transaction = Transaction::first();
        $this->assertSame(2000.0, (float) $transaction->discount_amount);
        $this->assertSame(18000.0, (float) $transaction->total);
        $this->assertSame(2000.0, (float) $transaction->change_amount);
    }

    public function test_menu_nonaktif_tidak_dapat_ditambahkan_ke_keranjang_pos(): void
    {
        $kasir = $this->kasir();
        $menu = $this->menu();
        $menu->update(['is_active' => false]);

        $this->actingAs($kasir)->post(route('pos.add'), [
            'menu_id' => $menu->id,
            'quantity' => 1,
        ])->assertSessionHas('error');

        $this->assertTrue(app(CartService::class)->isEmpty());
    }
}
