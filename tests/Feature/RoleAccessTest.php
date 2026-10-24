<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create(['role' => User::ROLE_MANAGER]);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    public function test_manager_dapat_mengakses_pengguna_dan_pengaturan(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('users.index'))->assertOk();
        $this->actingAs($manager)->get(route('settings.index'))->assertOk();
        $this->actingAs($manager)->get(route('reports.index'))->assertOk();
    }

    public function test_kasir_ditolak_pada_halaman_manajemen(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('users.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('menus.index'))->assertForbidden();
    }

    public function test_kasir_dapat_mengakses_pos_dan_absensi(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('pos.index'))->assertOk();
        $this->actingAs($kasir)->get(route('attendances.index'))->assertOk();
    }

    public function test_peran_owner_tidak_valid_lagi(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'baru@magezz.test',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_manager_dapat_membuat_akun_dengan_peran_manager_atau_kasir(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir-baru@magezz.test',
            'password' => 'password',
            'role' => 'kasir',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'kasir-baru@magezz.test',
            'role' => 'kasir',
        ]);
    }

    public function test_model_user_tidak_memiliki_role_owner(): void
    {
        $this->assertFalse(defined(User::class.'::ROLE_OWNER'));
        $this->assertFalse(method_exists(User::class, 'isOwner'));
    }

    public function test_label_peran_hanya_manager_dan_kasir(): void
    {
        $manager = $this->manager();
        $kasir = $this->kasir();

        $this->assertSame('Manager', $manager->roleLabel());
        $this->assertSame('Kasir / Barista', $kasir->roleLabel());
    }
}
