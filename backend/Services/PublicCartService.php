<?php

namespace App\Services;

use App\Models\Menu;

class PublicCartService
{
    private const SESSION_KEY = 'public_cart';

    /**
     * @return array<int, array{menu_id: int, menu_name: string, price: float, quantity: int, subtotal: float}>
     */
    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function isEmpty(): bool
    {
        return empty($this->all());
    }

    public function count(): int
    {
        return array_sum(array_column($this->all(), 'quantity'));
    }

    public function total(): float
    {
        return round(array_sum(array_column($this->all(), 'subtotal')), 2);
    }

    public function add(Menu $menu, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);
        $cart = $this->all();

        if (isset($cart[$menu->id])) {
            $cart[$menu->id]['quantity'] += $quantity;
        } else {
            $cart[$menu->id] = [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'price' => (float) $menu->price,
                'quantity' => $quantity,
            ];
        }

        $cart[$menu->id]['subtotal'] = round($cart[$menu->id]['price'] * $cart[$menu->id]['quantity'], 2);

        session([self::SESSION_KEY => $cart]);
    }

    public function update(int $menuId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->remove($menuId);
        }

        $cart = $this->all();

        if (! isset($cart[$menuId])) {
            return false;
        }

        $cart[$menuId]['quantity'] = $quantity;
        $cart[$menuId]['subtotal'] = round($cart[$menuId]['price'] * $quantity, 2);

        session([self::SESSION_KEY => $cart]);

        return true;
    }

    public function remove(int $menuId): bool
    {
        $cart = $this->all();

        if (! isset($cart[$menuId])) {
            return false;
        }

        unset($cart[$menuId]);

        session([self::SESSION_KEY => $cart]);

        return true;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Item keranjang yang masih valid (menu ada & aktif), beserta status ketersediaannya.
     *
     * @return array<int, array{menu_id: int, menu_name: string, price: float, quantity: int, subtotal: float, menu: Menu}>
     */
    public function validItems(): array
    {
        $menuIds = array_column($this->all(), 'menu_id');

        if (empty($menuIds)) {
            return [];
        }

        $menus = Menu::query()
            ->whereIn('id', $menuIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($this->all() as $item) {
            $menu = $menus[$item['menu_id']] ?? null;

            if (! $menu) {
                continue;
            }

            $item['menu'] = $menu;
            $items[] = $item;
        }

        return $items;
    }
}
