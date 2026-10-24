<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    private function today(): string
    {
        return now()->format('Y-m-d');
    }

    public function test_kasir_dapat_clock_in_dengan_jam_manual(): void
    {
        $kasir = $this->kasir();

        $response = $this->actingAs($kasir)->post(route('attendances.clock-in'), [
            'date' => $this->today(),
            'clock_in' => '08:30',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:30:00',
        ]);
    }

    public function test_clock_in_wajib_mengisi_jam(): void
    {
        $kasir = $this->kasir();

        $response = $this->actingAs($kasir)->post(route('attendances.clock-in'), [
            'date' => $this->today(),
            'clock_in' => '',
        ]);

        $response->assertSessionHasErrors('clock_in');
        $this->assertDatabaseMissing('attendances', ['user_id' => $kasir->id]);
    }

    public function test_absensi_user_dan_tanggal_harus_unik(): void
    {
        $kasir = $this->kasir();

        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:00:00',
        ]);

        $this->expectException(QueryException::class);

        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 09:00:00',
        ]);
    }

    public function test_manager_dapat_mengoreksi_absensi_kasir(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $kasir = $this->kasir();

        $this->actingAs($manager)->post(route('attendances.correct'), [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'reason' => 'Salah input jam pulang',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:00:00',
            'clock_out' => $this->today().' 17:00:00',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'edited_by' => $manager->id,
            'old_clock_in' => null,
            'old_clock_out' => null,
            'new_clock_in' => $this->today().' 08:00:00',
            'new_clock_out' => $this->today().' 17:00:00',
            'reason' => 'Salah input jam pulang',
        ]);
    }

    public function test_kasir_tidak_dapat_mengoreksi_absensi(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('attendances.correct'), [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => '08:00',
            'reason' => 'Mencoba koreksi',
        ])->assertForbidden();
    }

    public function test_koreksi_absensi_tanggal_future_ditolak(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $kasir = $this->kasir();

        $this->actingAs($manager)->post(route('attendances.correct'), [
            'user_id' => $kasir->id,
            'date' => today()->addDay()->format('Y-m-d'),
            'clock_in' => '08:00',
            'reason' => 'Koreksi tanggal future',
        ])->assertSessionHasErrors('date');

        $this->assertDatabaseMissing('attendances', ['user_id' => $kasir->id]);
    }

    public function test_koreksi_absensi_wajib_alasan(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $kasir = $this->kasir();

        $this->actingAs($manager)->post(route('attendances.correct'), [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => '08:00',
            'clock_out' => '17:00',
        ])->assertSessionHasErrors('reason');
    }

    public function test_koreksi_absensi_jam_keluar_tidak_boleh_sebelum_jam_masuk(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $kasir = $this->kasir();

        $this->actingAs($manager)->post(route('attendances.correct'), [
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => '17:00',
            'clock_out' => '08:00',
            'reason' => 'Uji validasi jam',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('attendances', ['user_id' => $kasir->id]);
    }

    public function test_kasir_tidak_dapat_clock_in_tanggal_lampau(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('attendances.clock-in'), [
            'date' => today()->subDay()->format('Y-m-d'),
            'clock_in' => '08:00',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('attendances', ['user_id' => $kasir->id]);
    }

    public function test_clock_in_ganda_ditolak(): void
    {
        $kasir = $this->kasir();
        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:00:00',
        ]);

        $response = $this->actingAs($kasir)->post(route('attendances.clock-in'), [
            'date' => $this->today(),
            'clock_in' => '09:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'clock_in' => $this->today().' 08:00:00',
        ]);
    }

    public function test_clock_out_wajib_setelah_clock_in(): void
    {
        $kasir = $this->kasir();

        $response = $this->actingAs($kasir)->post(route('attendances.clock-out'), [
            'date' => $this->today(),
            'clock_out' => '17:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', ['user_id' => $kasir->id]);
    }

    public function test_clock_out_dengan_jam_manual(): void
    {
        $kasir = $this->kasir();
        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:30:00',
        ]);

        $response = $this->actingAs($kasir)->post(route('attendances.clock-out'), [
            'date' => $this->today(),
            'clock_out' => '17:15',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'clock_in' => $this->today().' 08:30:00',
            'clock_out' => $this->today().' 17:15:00',
        ]);

        $attendance = Attendance::where('user_id', $kasir->id)->first();
        $this->assertSame(525, $attendance->durationMinutes());
    }

    public function test_clock_out_tidak_boleh_lebih_awal_dari_clock_in(): void
    {
        $kasir = $this->kasir();
        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:30:00',
        ]);

        $response = $this->actingAs($kasir)->post(route('attendances.clock-out'), [
            'date' => $this->today(),
            'clock_out' => '07:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'clock_out' => null,
        ]);
    }

    public function test_clock_out_ganda_ditolak(): void
    {
        $kasir = $this->kasir();
        Attendance::create([
            'user_id' => $kasir->id,
            'date' => $this->today(),
            'clock_in' => $this->today().' 08:00:00',
            'clock_out' => $this->today().' 16:00:00',
        ]);

        $response = $this->actingAs($kasir)->post(route('attendances.clock-out'), [
            'date' => $this->today(),
            'clock_out' => '17:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_buka_shift_tidak_menimpa_jam_masuk_manual(): void
    {
        // Kunci waktu agar jam manual 07:00 selalu berada di masa lalu,
        // bukan bergantung pada jam dinding saat tes dijalankan.
        $this->travelTo(Carbon::parse('2026-10-24 09:00:00'));

        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('attendances.clock-in'), [
            'date' => $this->today(),
            'clock_in' => '07:00',
        ]);

        $this->actingAs($kasir)->post(route('shifts.open'), [
            'opening_cash' => 100000,
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $kasir->id,
            'clock_in' => $this->today().' 07:00:00',
        ]);
    }

    public function test_buka_shift_mengisi_jam_masuk_bila_kosong(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('shifts.open'), [
            'opening_cash' => 100000,
        ]);

        $attendance = Attendance::where('user_id', $kasir->id)->whereDate('date', $this->today())->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
    }

    public function test_halaman_absensi_dapat_diakses_dengan_filter_tanggal(): void
    {
        $kasir = $this->kasir();

        $response = $this->actingAs($kasir)->get('/attendances?date='.$this->today());

        $response->assertStatus(200);
        $response->assertSee('Absensi Manual');
    }

    public function test_manager_dapat_melihat_dan_mengoreksi_absensi_karyawan_dari_halaman_absensi(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $kasir = $this->kasir();

        $this->actingAs($manager)
            ->get(route('attendances.index', [
                'user_id' => $kasir->id,
                'date' => $this->today(),
            ]))
            ->assertOk()
            ->assertSee($kasir->name)
            ->assertSee('Koreksi Absensi')
            ->assertSee('Rekap Operasional');
    }
}
