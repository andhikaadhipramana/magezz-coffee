<?php

namespace App\Services;

use App\Models\Menu;

class CartService
{
    private const SESSION_KEY = 'pos_cart';

    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function add(Menu $menu, int $quantity = 1, array $variant = [], float $extraPrice = 0): void
    {
        $cart = $this->all();
        $variantInfo = $this->buildVariantInfo($variant);
        $key = $menu->id.'|'.md5($variantInfo);
        $unitPrice = (float) $menu->price + $extraPrice;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
            $cart[$key]['subtotal'] = $cart[$key]['quantity'] * $cart[$key]['price'];
        } else {
            $cart[$key] = [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'variant_info' => $variantInfo,
                'price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
            ];
        }

        session([self::SESSION_KEY => $cart]);
    }

    /**
     * Tambahkan item dengan harga yang sudah tersimpan (snapshot dari pesanan
     * online), sehingga perubahan harga menu setelah pesanan dibuat tidak
     * mengubah jumlah yang harus dibayar customer.
     */
    public function addSnapshot(int $menuId, string $menuName, float $price, int $quantity, ?string $variantInfo = null): void
    {
        $cart = $this->all();
        $key = $menuId.'|'.md5($variantInfo ?? '');

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
            $cart[$key]['subtotal'] = $cart[$key]['quantity'] * $cart[$key]['price'];
        } else {
            $cart[$key] = [
                'menu_id' => $menuId,
                'menu_name' => $menuName,
                'variant_info' => $variantInfo,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
            ];
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $cart = $this->all();

        if (! isset($cart[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = $quantity;
            $cart[$key]['subtotal'] = $quantity * $cart[$key]['price'];
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(string $key): void
    {
        $cart = $this->all();
        unset($cart[$key]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return empty($this->all());
    }

    public function subtotal(): float
    {
        return array_sum(array_column($this->all(), 'subtotal'));
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->all(), 'quantity'));
    }

    public function calculate(?string $discountType, float $discountValue, bool $taxEnabled, float $taxRate): array
    {
        $subtotal = $this->subtotal();
        $discountAmount = 0.0;

        if ($discountType === 'percent' && $discountValue > 0) {
            $discountAmount = round($subtotal * min($discountValue, 100) / 100, 2);
        } elseif ($discountType === 'amount' && $discountValue > 0) {
            $discountAmount = min($discountValue, $subtotal);
        }

        $afterDiscount = max($subtotal - $discountAmount, 0);
        $taxAmount = $taxEnabled ? round($afterDiscount * $taxRate / 100, 2) : 0.0;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => $taxAmount,
            'total' => round($afterDiscount + $taxAmount, 2),
        ];
    }

    public function backup(): void
    {
        $cart = $this->all();
        if (! empty($cart)) {
            session(['pos_cart_backup' => $cart]);
        }
    }

    public function restore(): void
    {
        $backup = session('pos_cart_backup', []);
        if (! empty($backup)) {
            session(['pos_cart' => $backup]);
            session()->forget('pos_cart_backup');
        }
    }

    public function hasBackup(): bool
    {
        return ! empty(session('pos_cart_backup', []));
    }

    public function discardBackup(): void
    {
        session()->forget('pos_cart_backup');
    }

    private function buildVariantInfo(array $variant): ?string
    {
        $parts = array_filter([
            $variant['temperature'] ?? null,
            $variant['size'] ?? null,
            isset($variant['sugar']) && $variant['sugar'] !== '' ? 'Gula '.$variant['sugar'].'%' : null,
            ! empty($variant['extra_shot']) ? 'Extra Shot' : null,
            $variant['milk'] ?? null,
            $variant['topping'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
