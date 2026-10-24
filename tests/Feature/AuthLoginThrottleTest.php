<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create([
            'email' => 'manager@magezz.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    private function attempt(string $email, string $password = 'salah'): TestResponse
    {
        return $this->post(route('login'), [
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function errorMessage(TestResponse $response): string
    {
        return (string) $response->getSession()->get('errors')->first('email');
    }

    public function test_login_dikunci_lima_menit_setelah_tiga_kegagalan(): void
    {
        $manager = $this->manager();

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($manager->email)->assertSessionHasErrors('email');
        }

        $response = $this->attempt($manager->email);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('5 menit', $this->errorMessage($response));
        $this->assertGuest();
    }

    public function test_lockout_bereskalasi_setiap_tiga_kegagalan(): void
    {
        $manager = $this->manager();

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($manager->email);
        }

        $this->travel(5)->minutes();

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($manager->email);
        }

        $response = $this->attempt($manager->email);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('10 menit', $this->errorMessage($response));
        $this->assertGuest();
    }

    public function test_login_berhasil_mereset_lockout(): void
    {
        $manager = $this->manager();

        $this->attempt($manager->email);
        $this->attempt($manager->email);

        $this->post(route('login'), [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($manager);

        $this->post(route('logout'));

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($manager->email);
        }

        $response = $this->attempt($manager->email);

        $this->assertStringContainsString('5 menit', $this->errorMessage($response));
    }

    public function test_lockout_tidak_berlaku_untuk_email_lain(): void
    {
        $manager = $this->manager();
        $kasir = User::factory()->create([
            'email' => 'kasir@magezz.test',
            'password' => 'password',
            'role' => User::ROLE_KASIR,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($manager->email);
        }

        $this->post(route('login'), [
            'email' => $kasir->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($kasir);
    }
}
