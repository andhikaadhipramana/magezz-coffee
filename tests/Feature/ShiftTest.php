<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftTest extends TestCase
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

    private function openShift(User $user, float $openingCash = 100000): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'opening_cash' => $openingCash,
            'status' => Shift::STATUS_OPEN,
        ]);
    }

    public function test_manager_dapat_membuka_halaman_shift_dan_detail_shift(): void
    {
        $manager = $this->manager();
        $shift = $this->openShift($this->kasir());

        $this->actingAs($manager)
            ->get(route('shifts.index'))
            ->assertOk()
            ->assertSee('Riwayat Shift');

        $this->actingAs($manager)
            ->get(route('shifts.show', $shift))
            ->assertOk()
            ->assertSee('Tutup Shift')
            ->assertSee('Ditutup Oleh');
    }

    public function test_kasir_tidak_dapat_melihat_detail_shift_kasir_lain(): void
    {
        $kasir = $this->kasir();
        $shift = $this->openShift($this->kasir());

        $this->actingAs($kasir)
            ->get(route('shifts.show', $shift))
            ->assertForbidden();
    }

    public function test_manager_dapat_menutup_shift_kasir_dan_tercatat(): void
    {
        $manager = $this->manager();
        $shift = $this->openShift($this->kasir());

        $this->actingAs($manager)
            ->post(route('shifts.close', $shift), [
                'closing_cash' => 100000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $shift = $shift->fresh();

        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame($manager->id, $shift->closed_by);
        $this->assertNotNull($shift->ended_at);
        $this->assertSame(0.0, (float) $shift->cash_difference);
    }

    public function test_selisih_kas_wajib_disertai_catatan(): void
    {
        $kasir = $this->kasir();
        $shift = $this->openShift($kasir);

        $this->actingAs($kasir)
            ->post(route('shifts.close', $shift), [
                'closing_cash' => 90000,
            ])
            ->assertSessionHas('error');

        $this->assertSame(Shift::STATUS_OPEN, $shift->fresh()->status);
        $this->assertNull($shift->fresh()->closed_by);
    }

    public function test_shift_kedua_dalam_sehari_memperbarui_clock_out_ke_jam_paling_akhir(): void
    {
        $kasir = $this->kasir();

        $this->travelTo(Carbon::parse('2026-10-22 08:00:00'));
        $this->actingAs($kasir)->post(route('shifts.open'), ['opening_cash' => 100000]);
        $firstShift = Shift::query()->orderBy('id')->firstOrFail();

        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $this->actingAs($kasir)->post(route('shifts.close', $firstShift), ['closing_cash' => 100000]);

        $this->travelTo(Carbon::parse('2026-10-22 13:00:00'));
        $this->actingAs($kasir)->post(route('shifts.open'), ['opening_cash' => 100000]);
        $secondShift = Shift::query()->orderByDesc('id')->firstOrFail();

        $this->travelTo(Carbon::parse('2026-10-22 17:00:00'));
        $this->actingAs($kasir)->post(route('shifts.close', $secondShift), ['closing_cash' => 100000]);

        $attendance = Attendance::query()->firstOrFail();

        $this->assertSame('2026-10-22 08:00:00', $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-22 17:00:00', $attendance->clock_out->format('Y-m-d H:i:s'));
    }
}
