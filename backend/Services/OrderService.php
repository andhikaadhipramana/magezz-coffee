<?php

namespace App\Services;

/**
 * Service Layer - Logika bisnis pesanan (Backend).
 * Menangani pembuatan pesanan, update status, dan perhitungan
 * yang kompleks agar Controller tetap ramping.
 */

use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function generateCode(): string
    {
        $prefix = 'MGO-'.Carbon::now()->format('Ymd').'-';

        do {
            $code = $prefix.Str::upper(Str::random(6));
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Buat pesanan dari keranjang customer (item sudah tervalidasi).
     *
     * @param  array<int, array{menu_id: int, menu_name: string, price: float, quantity: int, subtotal: float, menu: Menu}>  $items
     * @param  array{customer_name?: ?string, table_number?: ?string, notes?: ?string}  $data
     */
    public function createFromItems(array $items, array $data): Order
    {
        $subtotal = round(array_sum(array_column($items, 'subtotal')), 2);
        $taxAmount = Setting::taxEnabled()
            ? round($subtotal * Setting::taxRate() / 100, 2)
            : 0.0;
        $total = round($subtotal + $taxAmount, 2);

        return DB::transaction(function () use ($items, $data, $subtotal, $taxAmount, $total) {
            $order = Order::create([
                'code' => $this->generateCode(),
                'customer_name' => $data['customer_name'] ?? null,
                'table_number' => $data['table_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => Order::STATUS_PENDING,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'menu_id' => $item['menu_id'],
                    'menu_name' => $item['menu_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $order;
        });
    }
}
