<?php

namespace App\Http\Controllers;

/**
 * Controller Backend-Kasir - Point of Sale (POS).
 * Mengelola transaksi penjualan di kasir, keranjang POS,
 * dan pembentukan transaksi. Hanya untuk role kasir/manager.
 */

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\CartService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PosController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $menus = Menu::query()
            ->with('category')
            ->where('is_active', true)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->get();

        $activeShift = $request->user()->activeShift();

        $totals = $this->cart->calculate(
            null,
            0,
            Setting::taxEnabled(),
            Setting::taxRate()
        );

        return view('pos.index', [
            'categories' => $categories,
            'menus' => $menus,
            'cart' => $this->cart->all(),
            'totals' => $totals,
            'activeShift' => $activeShift,
            'taxEnabled' => Setting::taxEnabled(),
            'taxRate' => Setting::taxRate(),
            'payingOrder' => Order::query()->with('items')->find(session('pos_order_id')),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'exists:menus,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'temperature' => ['nullable', 'string', 'max:20'],
            'size' => ['nullable', 'string', 'max:20'],
            'sugar' => ['nullable', 'string', 'max:10'],
            'extra_shot' => ['nullable', 'boolean'],
            'milk' => ['nullable', 'string', 'max:30'],
            'topping' => ['nullable', 'string', 'max:30'],
        ]);

        $menu = Menu::findOrFail($data['menu_id']);

        if (! $menu->is_active) {
            return back()->with('error', 'Menu '.$menu->name.' sedang tidak aktif dan tidak dapat ditambahkan ke keranjang.');
        }

        $extraPrice = 0;
        if ($request->boolean('extra_shot')) {
            $extraPrice += 8000;
        }
        if (($data['size'] ?? null) === 'Large') {
            $extraPrice += 5000;
        }
        if (($data['temperature'] ?? null) === 'Iced') {
            $extraPrice += 2000;
        }
        if (! empty($data['milk'])) {
            $extraPrice += 10000;
        }
        if (! empty($data['topping'])) {
            $extraPrice += 5000;
        }

        $this->cart->add($menu, (int) ($data['quantity'] ?? 1), $data, $extraPrice);

        return back()->with('success', $menu->name.' ditambahkan ke keranjang.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->cart->updateQuantity($data['key'], (int) $data['quantity']);

        return back();
    }

    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate(['key' => ['required', 'string']]);
        $this->cart->remove($data['key']);

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();
        session()->forget('pos_order_id');

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return back()->with('error', 'Keranjang masih kosong.');
        }

        $data = $request->validate([
            'discount_type' => ['nullable', 'in:none,percent,amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,card'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $discountType = $data['discount_type'] ?? 'none';
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $totals = $this->cart->calculate($discountType, $discountValue, Setting::taxEnabled(), Setting::taxRate());

        $paid = (float) ($data['paid_amount'] ?? 0);

        if ($data['payment_method'] === Transaction::PAYMENT_CASH && $paid < $totals['total']) {
            return back()->with('error', 'Jumlah uang tunai kurang dari total pembayaran.');
        }

        if ($data['payment_method'] !== Transaction::PAYMENT_CASH) {
            $paid = $totals['total'];
        }

        $change = max(0, $paid - $totals['total']);
        $shift = $request->user()->activeShift();

        $order = null;

        try {
            $transaction = DB::transaction(function () use ($data, $discountType, $discountValue, $totals, $paid, $change, $shift, $request, &$order) {
                $transaction = Transaction::create([
                    'code' => $this->transactionService->generateCode(),
                    'user_id' => $request->user()->id,
                    'shift_id' => $shift?->id,
                    'subtotal' => $totals['subtotal'],
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $totals['discount_amount'],
                    'tax_amount' => $totals['tax_amount'],
                    'total' => $totals['total'],
                    'payment_method' => $data['payment_method'],
                    'paid_amount' => $paid,
                    'change_amount' => $change,
                    'status' => Transaction::STATUS_COMPLETED,
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($this->cart->all() as $item) {
                    $transaction->items()->create([
                        'menu_id' => $item['menu_id'],
                        'menu_name' => $item['menu_name'],
                        'variant_info' => $item['variant_info'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);
                }

                $orderId = (int) session('pos_order_id', 0);

                if ($orderId > 0) {
                    $order = Order::query()->where('id', $orderId)->lockForUpdate()->first();

                    if (! $order) {
                        throw new RuntimeException('Pesanan yang hendak dibayar tidak ditemukan. Muat ulang dari halaman Pesanan Online.');
                    }

                    if ($order->isPaid() || $order->status !== Order::STATUS_PENDING) {
                        throw new RuntimeException('Pesanan '.$order->code.' sudah dibayar atau tidak lagi menunggu pembayaran.');
                    }

                    $order->markPaid($transaction, $request->user());
                }

                return $transaction;
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (isset($order)) {
            session()->forget('pos_order_id');
            $this->cart->clear();
            $this->cart->discardBackup();

            return redirect()
                ->route('transactions.receipt', $transaction)
                ->with('success', 'Pembayaran pesanan '.$order->code.' ('.$transaction->code.') diterima. Pesanan otomatis Diproses.');
        }

        $this->cart->clear();
        $this->cart->discardBackup();

        return redirect()
            ->route('transactions.receipt', $transaction)
            ->with('success', 'Transaksi '.$transaction->code.' berhasil.');
    }
}
