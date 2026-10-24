<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create(['role' => User::ROLE_MANAGER]);
    }

    private function kategori(): Category
    {
        return Category::create(['code' => 'KAT-TEST', 'name' => 'Kopi', 'is_active' => true]);
    }

    public function test_halaman_menu_tampil_tanpa_error(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('menus.index'))->assertOk()->assertSee('Manajemen Menu');
    }

    public function test_halaman_menu_tidak_lagi_menampilkan_bagian_resep_stok(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('menus.index'))
            ->assertOk()
            ->assertDontSee('Resep Bahan Baku')
            ->assertDontSee('Stok Bahan Baku');
    }

    public function test_manager_dapat_menambah_menu_dengan_signature(): void
    {
        $manager = $this->manager();
        $category = $this->kategori();

        $response = $this->actingAs($manager)->post(route('menus.store'), [
            'category_id' => $category->id,
            'code' => 'MNU-BARU',
            'name' => 'Kopi Baru',
            'price' => 25000,
            'is_signature' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $menu = Menu::where('code', 'MNU-BARU')->firstOrFail();
        $this->assertSame('Kopi Baru', $menu->name);
        $this->assertTrue($menu->is_signature);
        $this->assertTrue($menu->is_active);
    }

    public function test_manager_dapat_memperbarui_menu(): void
    {
        $manager = $this->manager();
        $category = $this->kategori();

        $menu = Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-EDIT',
            'name' => 'Menu Awal',
            'price' => 20000,
            'is_signature' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->put(route('menus.update', $menu), [
            'category_id' => $category->id,
            'code' => 'MNU-EDIT',
            'name' => 'Menu Diubah',
            'price' => 30000,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $menu->refresh();
        $this->assertSame('Menu Diubah', $menu->name);
        $this->assertSame(30000.0, (float) $menu->price);
        $this->assertFalse($menu->is_signature);
        $this->assertTrue($menu->is_active);
    }

    public function test_manager_dapat_menonaktifkan_menu(): void
    {
        $manager = $this->manager();
        $category = $this->kategori();

        $menu = Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-OFF',
            'name' => 'Menu Nonaktif',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($manager)->delete(route('menus.destroy', $menu))->assertSessionHas('success');

        $this->assertFalse($menu->fresh()->is_active);
    }

    public function test_manager_dapat_mengunggah_foto_menu(): void
    {
        Storage::fake('menus');

        $manager = $this->manager();
        $category = $this->kategori();

        $response = $this->actingAs($manager)->post(route('menus.store'), [
            'category_id' => $category->id,
            'code' => 'MNU-FOTO',
            'name' => 'Kopi Foto',
            'price' => 25000,
            'image' => UploadedFile::fake()->image('kopi.jpg', 800, 600),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $menu = Menu::where('code', 'MNU-FOTO')->firstOrFail();

        $this->assertNotNull($menu->image_path);
        Storage::disk('menus')->assertExists($menu->image_path);
        $this->assertSame(Storage::disk('menus')->url($menu->image_path), $menu->image_url);
    }

    public function test_manager_dapat_mengganti_dan_menghapus_foto_menu(): void
    {
        Storage::fake('menus');

        $manager = $this->manager();
        $category = $this->kategori();
        $menu = Menu::create([
            'category_id' => $category->id,
            'code' => 'MNU-GANTI-FOTO',
            'name' => 'Menu Foto',
            'price' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($manager)->put(route('menus.update', $menu), [
            'category_id' => $category->id,
            'code' => 'MNU-GANTI-FOTO',
            'name' => 'Menu Foto',
            'price' => 20000,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('pertama.jpg', 800, 600),
        ])->assertSessionHasNoErrors();

        $firstPath = $menu->fresh()->image_path;
        $this->assertNotNull($firstPath);
        Storage::disk('menus')->assertExists($firstPath);

        $this->actingAs($manager)->put(route('menus.update', $menu), [
            'category_id' => $category->id,
            'code' => 'MNU-GANTI-FOTO',
            'name' => 'Menu Foto',
            'price' => 20000,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('kedua.jpg', 800, 600),
        ])->assertSessionHasNoErrors();

        $secondPath = $menu->fresh()->image_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('menus')->assertMissing($firstPath);
        Storage::disk('menus')->assertExists($secondPath);

        $this->actingAs($manager)->put(route('menus.update', $menu), [
            'category_id' => $category->id,
            'code' => 'MNU-GANTI-FOTO',
            'name' => 'Menu Foto',
            'price' => 20000,
            'is_active' => '1',
            'remove_image' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($menu->fresh()->image_path);
        Storage::disk('menus')->assertMissing($secondPath);
    }

    public function test_upload_foto_harus_berupa_gambar_yang_valid(): void
    {
        Storage::fake('menus');

        $manager = $this->manager();
        $category = $this->kategori();

        $this->actingAs($manager)->post(route('menus.store'), [
            'category_id' => $category->id,
            'code' => 'MNU-DOKUMEN',
            'name' => 'Menu Dokumen',
            'price' => 20000,
            'image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('menus', ['code' => 'MNU-DOKUMEN']);
    }
}
