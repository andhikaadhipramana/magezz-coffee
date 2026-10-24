<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Support\MenuImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $menus = Menu::query()
            ->with('category')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('menus.index', [
            'menus' => $menus,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Menu::create([
            'category_id' => $data['category_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $this->storeImage($request),
            'price' => $data['price'],
            'is_signature' => $request->boolean('is_signature'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validated($request, $menu);
        $imagePath = $menu->image_path;

        if ($newImage = $this->storeImage($request)) {
            $this->deleteImage($imagePath);
            $imagePath = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($imagePath);
            $imagePath = null;
        }

        $menu->update([
            'category_id' => $data['category_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $imagePath,
            'price' => $data['price'],
            'is_signature' => $request->boolean('is_signature'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->update(['is_active' => false]);

        return back()->with('success', 'Menu berhasil dinonaktifkan.');
    }

    private function validated(Request $request, ?Menu $menu = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:20', Rule::unique('menus', 'code')->ignore($menu?->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('', 'menus');

        if (! is_string($path)) {
            return null;
        }

        // Siapkan varian WebP agar katalog tetap ringan; gagal pun tidak menggagalkan simpan.
        try {
            MenuImage::generate($path);
        } catch (\Throwable $e) {
            report($e);
        }

        return $path;
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        MenuImage::deleteVariants($path);
        Storage::disk('menus')->delete($path);
    }
}
