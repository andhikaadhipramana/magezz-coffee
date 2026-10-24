<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRevenueIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_KASIR,
        ]);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'name' => 'Manager Uji',
            'email' => 'manager.uji@magezz.test',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    private function transaksi(User $user, float $total, string $code, string $menuName = 'Kopi Susu'): Transaction
    {
        $transaction = Transaction::query()->create([
            'code' => $code,
            'user_id' => $user->id,
            'subtotal' => $total,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'payment_method' => Transaction::PAYMENT_CASH,
            'paid_amount' => $total,
            'change_amount' => 0,
            'status' => Transaction::STATUS_COMPLETED,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_name' => $menuName,
            'price' => $total,
            'quantity' => 1,
            'subtotal' => $total,
        ]);

        return $transaction;
    }

    private function shift(User $user, float $difference): Shift
    {
        return Shift::query()->create([
            'user_id' => $user->id,
            'started_at' => now()->subHours(4),
            'ended_at' => now(),
            'opening_cash' => 100000,
            'closing_cash' => 100000 + $difference,
            'expected_cash' => 100000,
            'cash_difference' => $difference,
            'status' => Shift::STATUS_CLOSED,
        ]);
    }

    public function test_kasir_hanya_melihat_shift_dan_ringkasan_miliknya(): void
    {
        $alexander = $this->kasir('Alexander', 'alexander@magezz.test');
        $veronica = $this->kasir('Veronica', 'veronica@magezz.test');

        $this->shift($alexander, 5000);
        $this->shift($veronica, -7000);

        $response = $this->actingAs($alexander)->get(route('shifts.index'));

        $response->assertOk();
        $response->assertDontSee('Veronica');
        $response->assertViewHas('shifts', fn ($shifts) => $shifts->total() === 1
            && $shifts->first()->user_id === $alexander->id);
        $response->assertViewHas('summary', fn ($summary) => (int) $summary->total === 1
            && (float) $summary->difference === 5000.0);
    }

    public function test_kasir_tidak_bisa_melihat_transaksi_kasir_lain(): void
    {
        $alexander = $this->kasir('Alexander', 'alexander@magezz.test');
        $veronica = $this->kasir('Veronica', 'veronica@magezz.test');

        $trxAlexander = $this->transaksi($alexander, 50000, 'TRX-ALX-001');
        $trxVeronica = $this->transaksi($veronica, 75000, 'TRX-VRC-001', 'Americano');

        $this->actingAs($alexander)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('TRX-ALX-001')
            ->assertDontSee('TRX-VRC-001');

        $this->actingAs($alexander)->get(route('transactions.show', $trxAlexander))->assertOk();
        $this->actingAs($alexander)->get(route('transactions.show', $trxVeronica))->assertForbidden();
        $this->actingAs($alexander)->get(route('transactions.receipt', $trxVeronica))->assertForbidden();
    }

    public function test_dashboard_kasir_hanya_menghitung_omzet_miliknya(): void
    {
        $alexander = $this->kasir('Alexander', 'alexander@magezz.test');
        $veronica = $this->kasir('Veronica', 'veronica@magezz.test');

        $this->transaksi($alexander, 50000, 'TRX-ALX-002');
        $this->transaksi($veronica, 75000, 'TRX-VRC-002');

        $response = $this->actingAs($alexander)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todayRevenue', 50000.0);
        $response->assertViewHas('monthRevenue', 50000.0);
        $response->assertViewHas('todayTrx', 1);
        $response->assertSee('TRX-ALX-002');
        $response->assertDontSee('TRX-VRC-002');
    }

    public function test_manager_tetap_melihat_data_semua_kasir(): void
    {
        $manager = $this->manager();
        $alexander = $this->kasir('Alexander', 'alexander@magezz.test');
        $veronica = $this->kasir('Veronica', 'veronica@magezz.test');

        $shiftAlexander = $this->shift($alexander, 5000);
        $shiftVeronica = $this->shift($veronica, -7000);

        $this->transaksi($alexander, 50000, 'TRX-ALX-003');
        $trxVeronica = $this->transaksi($veronica, 75000, 'TRX-VRC-003');

        $this->actingAs($manager)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('TRX-ALX-003')
            ->assertSee('TRX-VRC-003');

        $this->actingAs($manager)->get(route('transactions.show', $trxVeronica))->assertOk();

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('todayRevenue', 125000.0);

        $this->actingAs($manager)
            ->get(route('shifts.index'))
            ->assertOk()
            ->assertSee('#'.$shiftAlexander->id)
            ->assertSee('#'.$shiftVeronica->id);
    }
}
