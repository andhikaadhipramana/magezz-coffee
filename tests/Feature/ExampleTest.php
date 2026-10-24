<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman root menampilkan katalog menu publik (tanpa login).
     */
    public function test_root_menampilkan_katalog_menu_publik(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Login Staf');
    }
}
