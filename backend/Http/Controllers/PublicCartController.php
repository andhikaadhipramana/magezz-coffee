<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Setting;
use App\Services\PublicCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCartController extends Controller
{
    public function __construct(private readonly PublicCartService $cart) {}

    public function index(): View
    {
        $items = $this->cart->validItems();

        return view('public.cart', array_merge([
            'items' => $items,
            'count' => $this->cart->count(),
        ], $this->calculateTotals()));
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'redirect' => ['nullable', 'in:cart'],
        ]);

        $menu = Menu::query()->findOrFail($data['menu_id']);

        if (! $menu->is_active) {
            return $this->addFailed($request, 'Menu tersebut sudah tidak tersedia.');
        }

        $this->cart->add($menu, (int) ($data['quantity'] ?? 1));

        $message = $menu->name.' ditambahkan ke keranjang.';

        if (! $request->expectsJson() && ($data['redirect'] ?? null) === 'cart') {
            return redirect()->route('public.cart.index')->with('success', $message);
        }

        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
                'count' => $this->cart->count(),
            ], $this->calculateTotals()));
        }

        return back()->with('success', $message);
    }

    private function addFailed(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'count' => $this->cart->count(),
            ]);
        }

        return back()->with('error', $message);
    }

    public function count(): JsonResponse
    {
        return response()->json(array_merge([
            'count' => $this->cart->count(),
        ], $this->calculateTotals()));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $updated = $this->cart->update((int) $data['menu_id'], (int) $data['quantity']);

        if (! $updated) {
            return back()->with('error', 'Item tidak ditemukan di keranjang.');
        }

        return back()->with('success', 'Jumlah pesanan diperbarui.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate(['menu_id' => ['required', 'integer']]);

        if (! $this->cart->remove((int) $data['menu_id'])) {
            return back()->with('error', 'Item tidak ditemukan di keranjang.');
        }

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    /**
     * Ringkasan nilai keranjang termasuk pajak, supaya total yang dilihat
     * customer sama dengan yang ditagih kasir.
     *
     * @return array{subtotal: float, tax_enabled: bool, tax_rate: float, tax_amount: float, total: float}
     */
    private function calculateTotals(): array
    {
        $subtotal = $this->cart->total();
        $taxEnabled = Setting::taxEnabled();
        $taxRate = Setting::taxRate();
        $taxAmount = $taxEnabled ? round($subtotal * $taxRate / 100, 2) : 0.0;

        return [
            'subtotal' => $subtotal,
            'tax_enabled' => $taxEnabled,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }
}
