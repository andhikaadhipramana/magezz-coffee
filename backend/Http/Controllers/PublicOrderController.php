<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\PublicCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function __construct(
        private readonly PublicCartService $cart,
        private readonly OrderService $orderService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()
                ->route('public.menu')
                ->with('error', 'Keranjang masih kosong.');
        }

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:50'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $items = $this->cart->validItems();

        if (empty($items)) {
            $this->cart->clear();

            return redirect()
                ->route('public.menu')
                ->with('error', 'Isi keranjang sudah tidak tersedia. Silakan pilih menu lagi.');
        }

        $order = $this->orderService->createFromItems($items, $data);

        $this->cart->clear();

        // simpan kode agar customer mudah kembali ke halaman status
        session(['public_last_order' => $order->code]);

        return redirect()
            ->route('public.order.show', $order->code)
            ->with('success', 'Pesanan berhasil dikirim ke kasir. Tunjukkan kode '.$order->code.' saat pembayaran.');
    }

    public function show(string $code): View
    {
        $order = Order::query()
            ->with(['items', 'transaction'])
            ->where('code', $code)
            ->firstOrFail();

        return view('public.order', [
            'order' => $order,
            'outletName' => Setting::get('outlet_name', 'Magezz Coffee'),
        ]);
    }

    /**
     * Halaman "Cek Status Pesanan": customer memasukkan kode pesanan
     * untuk kembali ke halaman status tanpa perlu bookmark link.
     */
    public function check(Request $request): View|RedirectResponse
    {
        $code = strtoupper(trim((string) $request->query('code', '')));

        if ($code !== '') {
            $order = Order::query()->where('code', $code)->first();

            if ($order === null) {
                return view('public.check', [
                    'code' => $code,
                    'lastCode' => session('public_last_order'),
                    'error' => 'Kode pesanan "'.$code.'" tidak ditemukan. Periksa kembali kode di struk Anda.',
                ]);
            }

            return redirect()->route('public.order.show', $order->code);
        }

        return view('public.check', [
            'code' => '',
            'lastCode' => session('public_last_order'),
        ]);
    }
}
