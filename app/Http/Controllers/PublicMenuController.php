<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicMenuController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $menus = Menu::query()
            ->with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('name')
            ->get();

        $grouped = $menus->groupBy('category_id');

        $menusByCategory = $categories
            ->filter(fn (Category $category) => $grouped->has($category->id))
            ->mapWithKeys(fn (Category $category) => [$category->id => $grouped->get($category->id)]);

        $signatureMenu = Menu::query()
            ->where('is_active', true)
            ->where('is_signature', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->orderBy('code')
            ->first();

        return view('public.menu', [
            'outletName' => Setting::get('outlet_name', 'Magezz Coffee'),
            'categories' => $categories,
            'menusByCategory' => $menusByCategory,
            'signatureMenu' => $signatureMenu,
            'search' => $search,
            'activeCategoryId' => $categoryId,
            'totalMenus' => $menus->count(),
        ]);
    }
}
