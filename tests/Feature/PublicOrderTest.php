<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CartService;
use App\Services\PublicCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicOrderTest extends TestCase
{
    use RefreshDatabase;

    private PublicCartService $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cart = app(PublicCartService::class);
    }

    private function menu(): Menu
    {
        $category = Category::create(['code' => 'KAT-ORD-'.uniqid(), 'name' => 'Kopi', 'is_active' => true]);

        return Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-ORD-'.uniqid(),
            'name' => 'Kopi Susu',
            'price' => 20000,
            'is_active' => true,
        ]);
    }

    private function isiKeranjang(Menu $menu, int $quantity): void
    {
        $this->post(route('public.cart.add'), ['menu_id' => $menu->id, 'quantity' => $quantity])->assertRedirect();
    }

    public function test_tamu_dapat_mengirim_pesanan_dan_melihat_statusnya(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 2);

        $response = $this->post(route('public.order.store'), [
            'customer_name' => 'Budi',
            'table_number' => '5',
            'notes' => 'Gula dikurangi',
        ]);

        $order = Order::query()->sole();
        $response->assertRedirect(route('public.order.show', $order->code));

        $this->assertStringStartsWith('MGO-', $order->code);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(40000.0, (float) $order->subtotal);
        $this->assertSame(4000.0, (float) $order->tax_amount);
        $this->assertSame(44000.0, (float) $order->total);
        $this->assertSame('Budi', $order->customer_name);
        $this->assertSame('5', $order->table_number);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(2, $order->items()->first()->quantity);

        // keranjang dikosongkan setelah pesanan dikirim
        $this->assertTrue($this->cart->isEmpty());

        // halaman status dapat dilihat tamu
        $this->get(route('public.order.show', $order->code))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('Belum dibayar')
            ->assertSee('Kopi Susu')
            ->assertSee('Gula dikurangi');
    }

    public function test_kode_pesanan_memakai_suffix_acak_dan_unik(): void
    {
        $menu = $this->menu();

        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'))->assertRedirect();

        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'))->assertRedirect();

        $codes = Order::query()->orderBy('id')->pluck('code');

        $this->assertCount(2, $codes);
        $this->assertNotSame($codes[0], $codes[1]);
        $this->assertMatchesRegularExpression('/^MGO-\d{8}-[A-Z0-9]{6}$/', $codes[0]);
        $this->assertMatchesRegularExpression('/^MGO-\d{8}-[A-Z0-9]{6}$/', $codes[1]);
    }

    public function test_pembuatan_pesanan_dibatasi_sepuluh_per_menit(): void
    {
        $menu = $this->menu();

        for ($i = 0; $i < 10; $i++) {
            $this->isiKeranjang($menu, 1);
            $this->post(route('public.order.store'))->assertRedirect();
        }

        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'))->assertStatus(429);
    }

    public function test_pesanan_dengan_keranjang_kosong_ditolak(): void
    {
        $this->post(route('public.order.store'), ['table_number' => '1'])
            ->assertRedirect(route('public.menu'))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_menu_yang_dihapus_dari_db_dibuang_dari_pesanan(): void
    {
        $category = Category::create(['code' => 'KAT-DEL', 'name' => 'Kopi', 'is_active' => true]);
        $menu = Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-DEL',
            'name' => 'Menu Dihapus',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->isiKeranjang($menu, 1);
        $menu->delete();

        $this->post(route('public.order.store'), ['table_number' => '3'])
            ->assertRedirect(route('public.menu'))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertTrue($this->cart->isEmpty());
    }

    public function test_kode_pesanan_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('public.order.show', 'MGO-TIDAK-ADA'))->assertNotFound();
    }

    /**
     * Kasir memuat pesanan ke POS lalu checkout: pesanan otomatis lunas
     * dan berstatus Diproses.
     */
    private function bayarkan(Order $order): Transaction
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)->post(route('orders.pay', $order))
            ->assertRedirect(route('pos.index'))
            ->assertSessionHas('success');

        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => Transaction::PAYMENT_CASH,
            'paid_amount' => (float) $order->total + 10000,
        ])->assertRedirect();

        return Transaction::query()->sole();
    }

    public function test_kasir_dapat_memproses_pemesanan_hingga_selesai(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '4']);
        $order = Order::query()->sole();

        // tanpa pembayaran, kasir tidak bisa langsung "Proses"
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_IN_PROGRESS])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);

        // tombol bayar: item masuk keranjang POS dengan harga saat pesan
        $this->actingAs($kasir)->post(route('orders.pay', $order))
            ->assertRedirect(route('pos.index'))
            ->assertSessionHas('success');

        $cart = app(CartService::class)->all();
        $this->assertCount(1, $cart);
        $this->assertSame(20000.0, (float) reset($cart)['price']);

        // checkout POS: transaksi tercatat + pesanan lunas & Diproses
        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => Transaction::PAYMENT_CASH,
            'paid_amount' => (float) $order->total + 10000,
        ])->assertRedirect();

        $transaction = Transaction::query()->sole();

        $order = $order->fresh();
        $this->assertNotNull($order->paid_at);
        $this->assertSame($transaction->id, $order->transaction_id);
        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->status);
        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->status);

        // keranjang POS kembali kosong dan penanda pesanan hilang
        $this->assertTrue(app(CartService::class)->isEmpty());

        // baner pembayaran hilang dan penjualan tunai biasa tetap bisa jalan
        $this->actingAs($kasir)->get(route('pos.index'))
            ->assertOk()
            ->assertDontSee('Membayar pesanan');

        $this->actingAs($kasir)->post(route('pos.add'), ['menu_id' => $menu->id]);
        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => Transaction::PAYMENT_CASH,
            'paid_amount' => 25000,
        ])->assertRedirect();

        $this->assertSame(2, Transaction::count());
        $this->assertSame($transaction->id, $order->fresh()->transaction_id);

        // lanjutan status normal
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_READY])
            ->assertRedirect();
        $this->assertSame(Order::STATUS_READY, $order->fresh()->status);

        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_COMPLETED])
            ->assertRedirect();
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
    }

    public function test_tamu_tidak_dapat_membayar_pesanan_langsung(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => 'A1']);
        $order = Order::query()->sole();

        $this->post(route('orders.pay', $order))->assertRedirect(route('login'));
        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_bayar_dengan_keranjang_kasir_masih_berisi_memuat_pesanan_ke_keranjang(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $menuLain = $this->menu();

        // kasir sedang melayani pembeli lain di POS
        $this->actingAs($kasir)->post(route('pos.add'), ['menu_id' => $menuLain->id]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => 'A2']);
        $order = Order::query()->sole();

        // tombol bayar: keranjang kasir yang berisi di-backup, lalu diganti pesanan online
        $this->actingAs($kasir)->post(route('orders.pay', $order))
            ->assertRedirect(route('pos.index'))
            ->assertSessionHas('success');

        // pesanan online dimuat ke keranjang POS dengan harga snapshot saat pesan
        $cart = app(CartService::class)->all();
        $this->assertSame(1, count($cart));
        $this->assertSame($menu->id, reset($cart)['menu_id']);
        $this->assertSame(20000.0, (float) reset($cart)['price']);

        // checkout POS: pesanan lunas dan transaksi tercatat
        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => Transaction::PAYMENT_CASH,
            'paid_amount' => (float) $order->total + 10000,
        ])->assertRedirect();

        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->fresh()->status);
    }

    public function test_bayar_dua_kali_tidak_diperbolehkan(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => 'B1']);
        $order = Order::query()->sole();

        $this->bayarkan($order);

        // mencoba membayar lagi setelah lunas
        $this->actingAs($kasir)->post(route('orders.pay', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Transaction::count());
        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->fresh()->status);
    }

    public function test_batal_sebelum_bayar_dan_setelah_bayar(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        // --- batal saat belum lunas: tidak ada transaksi ---
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => 'C1']);
        $order = Order::query()->sole();

        $this->actingAs($kasir)->post(route('orders.update-status', $order), [
            'status' => Order::STATUS_CANCELLED,
            'reason' => 'Customer tidak jadi pesan',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = $order->fresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame('Customer tidak jadi pesan', $order->cancel_reason);
        $this->assertSame($kasir->id, $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(0, Transaction::count());

        // --- batal setelah lunas: transaksi ikut di-void ---
        $menu2 = $this->menu();
        $this->isiKeranjang($menu2, 1);
        $this->post(route('public.order.store'), ['table_number' => 'C2']);
        $order2 = Order::query()->where('id', '!=', $order->id)->sole();

        $this->bayarkan($order2);

        $this->actingAs($kasir)->post(route('orders.update-status', $order2), [
            'status' => Order::STATUS_CANCELLED,
            'reason' => 'Barista salah input pesanan',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order2 = $order2->fresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order2->status);
        $this->assertSame(Transaction::STATUS_VOID, $order2->transaction->status);
        $this->assertSame(
            'Pembatalan pesanan '.$order2->code.': Barista salah input pesanan',
            $order2->transaction->void_reason
        );
        $this->assertSame($kasir->id, $order2->transaction->voided_by);
    }

    public function test_pesanan_selesai_tidak_dapat_dibatalkan(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => 'F1']);
        $order = Order::query()->sole();

        $this->bayarkan($order);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_READY]);
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_COMPLETED]);
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);

        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_CANCELLED])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame(Transaction::STATUS_COMPLETED, $order->fresh()->transaction->status);
    }

    public function test_kasir_dapat_membatalkan_pesanan_baru(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '5']);
        $order = Order::query()->sole();

        $this->actingAs($kasir)->post(route('orders.update-status', $order), [
            'status' => Order::STATUS_CANCELLED,
            'reason' => 'Stok menu habis',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = $order->fresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('Stok menu habis', $order->cancel_reason);
        $this->assertSame($kasir->id, $order->cancelled_by);
    }

    public function test_pembatalan_pesanan_wajib_menyertakan_alasan(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '5B']);
        $order = Order::query()->sole();

        $this->actingAs($kasir)->post(route('orders.update-status', $order), [
            'status' => Order::STATUS_CANCELLED,
        ])->assertSessionHasErrors('reason');

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertNull($order->fresh()->cancel_reason);
    }

    public function test_total_pesanan_tanpa_pajak_bila_pajak_dimatikan(): void
    {
        Setting::set('tax_enabled', '0');

        $menu = $this->menu();
        $this->isiKeranjang($menu, 2);
        $this->post(route('public.order.store'));

        $order = Order::query()->sole();

        $this->assertSame(40000.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->tax_amount);
        $this->assertSame(40000.0, (float) $order->total);
    }

    public function test_keranjang_publik_menampilkan_rincian_pajak(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 2);

        $this->get(route('public.cart.index'))
            ->assertOk()
            ->assertSee('Subtotal')
            ->assertSee('Pajak PB1 10%')
            ->assertSee('Total sudah termasuk pajak');
    }

    public function test_transisi_status_tidak_valid_ditolak(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '6']);
        $order = Order::query()->sole();

        // pending langsung ke completed tidak diizinkan
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_COMPLETED])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_tamu_tidak_dapat_mengubah_status_pesanan(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '7']);
        $order = Order::query()->sole();

        $this->post(route('orders.update-status', $order), ['status' => Order::STATUS_IN_PROGRESS])
            ->assertRedirect(route('login'));

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_pesanan_tampil_di_halaman_staf(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $menu = $this->menu();
        $this->isiKeranjang($menu, 2);
        $this->post(route('public.order.store'), ['table_number' => '8', 'customer_name' => 'Sinta']);
        $order = Order::query()->sole();

        $this->actingAs($kasir)->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Sinta')
            ->assertSee('Menunggu Pembayaran');

        // filter status
        $this->actingAs($kasir)->get(route('orders.index', ['status' => Order::STATUS_PENDING]))
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_pesanan_terakhir_tersimpan_dan_tampil_di_katalog(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);

        $this->post(route('public.order.store'), ['table_number' => '9']);
        $order = Order::query()->sole();

        // katalog menampilkan baner pesanan terakhir dengan tautan status
        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('Pesanan terakhir')
            ->assertSee($order->code)
            ->assertSee(route('public.order.show', $order->code), false);

        // baner tidak tampil di halaman status itu sendiri (redundan)
        $this->get(route('public.order.show', $order->code))
            ->assertOk()
            ->assertDontSee('Pesanan terakhir');
    }

    public function test_halaman_cek_status_menemukan_pesanan_dan_kode_tidak_dikenal(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '10']);
        $order = Order::query()->sole();

        // form kosong tampil
        $this->get(route('public.order.check'))
            ->assertOk()
            ->assertSee('Cek Status Pesanan');

        // kode dikenal -> diarahkan ke halaman status
        $this->get(route('public.order.check', ['code' => $order->code]))
            ->assertRedirect(route('public.order.show', $order->code));

        // kode tidak dikenal -> pesan tidak ditemukan
        $this->get(route('public.order.check', ['code' => 'MGO-XXXXXXXX-9999']))
            ->assertOk()
            ->assertSee('tidak ditemukan');

        // pesanan terakhir tersedia sebagai pintasan di halaman cek
        $this->get(route('public.order.check'))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Buka');
    }

    public function test_halaman_status_auto_refresh_hingga_selesai(): void
    {
        $menu = $this->menu();
        $this->isiKeranjang($menu, 1);
        $this->post(route('public.order.store'), ['table_number' => '11']);
        $order = Order::query()->sole();

        $show = fn (): TestResponse => $this->get(route('public.order.show', $order->code));

        // menunggu pembayaran masih memuat meta refresh
        $show()->assertOk()->assertSee('http-equiv="refresh"', false);

        $this->bayarkan($order);
        $show()->assertOk()->assertSee('http-equiv="refresh"', false)->assertSee('Lunas');

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_READY]);
        $show()->assertOk()->assertSee('http-equiv="refresh"', false);

        // selesai -> tidak ada lagi meta refresh
        $this->actingAs($kasir)->post(route('orders.update-status', $order), ['status' => Order::STATUS_COMPLETED]);
        $show()->assertOk()->assertDontSee('http-equiv="refresh"', false);
    }
}
