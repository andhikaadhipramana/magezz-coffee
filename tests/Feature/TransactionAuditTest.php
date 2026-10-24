<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAuditTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => User::ROLE_MANAGER]);
    }

    private function transaksi(User $kasir): Transaction
    {
        $category = Category::create(['code' => 'KAT-TRX', 'name' => 'Kopi', 'is_active' => true]);

        $menu = Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-TRX',
            'name' => 'Kopi Susu',
            'price' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($kasir)->post(route('pos.add'), [
            'menu_id' => $menu->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($kasir)->post(route('pos.checkout'), [
            'payment_method' => 'cash',
            'paid_amount' => 100000,
        ])->assertRedirect();

        return Transaction::query()->sole();
    }

    public function test_void_transaksi_mencatat_alasan_pelaku_dan_waktu(): void
    {
        $manager = $this->manager();
        $transaction = $this->transaksi($this->kasir());

        $this->actingAs($manager)->post(route('transactions.void', $transaction), [
            'reason' => 'Salah input pesanan',
        ])->assertRedirect()->assertSessionHas('success');

        $transaction = $transaction->fresh();

        $this->assertSame(Transaction::STATUS_VOID, $transaction->status);
        $this->assertSame('Salah input pesanan', $transaction->void_reason);
        $this->assertSame($manager->id, $transaction->voided_by);
        $this->assertNotNull($transaction->voided_at);
    }

    public function test_void_transaksi_wajib_menyertakan_alasan(): void
    {
        $manager = $this->manager();
        $transaction = $this->transaksi($this->kasir());

        $this->actingAs($manager)->post(route('transactions.void', $transaction), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
    }

    public function test_kasir_tidak_dapat_membatalkan_transaksi(): void
    {
        $kasir = $this->kasir();
        $transaction = $this->transaksi($kasir);

        $this->actingAs($kasir)->post(route('transactions.void', $transaction), [
            'reason' => 'Mencoba void',
        ])->assertForbidden();

        $this->assertSame(Transaction::STATUS_COMPLETED, $transaction->fresh()->status);
    }

    public function test_halaman_detail_menampilkan_riwayat_void(): void
    {
        $manager = $this->manager();
        $transaction = $this->transaksi($this->kasir());

        $this->actingAs($manager)->post(route('transactions.void', $transaction), [
            'reason' => 'Salah input pesanan',
        ])->assertRedirect();

        $this->actingAs($manager)->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Alasan Void')
            ->assertSee('Salah input pesanan')
            ->assertSee('Dibatalkan Oleh')
            ->assertSee($manager->name);
    }
}
