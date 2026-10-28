<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CartService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffOrderController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->filled('status') ? $request->status : null;

        $orders = Order::query()
            ->with(['items', 'transaction', 'cancelledBy'])
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('orders.index', [
            'orders' => $orders,
            'status' => $status,
            'counts' => $counts,
            'pendingCount' => (int) ($counts[Order::STATUS_PENDING] ?? 0),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,ready,completed,cancelled'],
        ]);

        $next = $data['status'];

        if (! in_array($next, $order->nextStatuses(), true)) {
            return back()->with('error', 'Perubahan status tidak valid untuk pesanan ini.');
        }

        if ($next === Order::STATUS_CANCELLED) {
            $cancelData = $request->validate([
                'reason' => ['required', 'string', 'max:255'],
            ], [
                'reason.required' => 'Alasan pembatalan pesanan wajib diisi.',
            ]);

            return back()->with('success', $this->cancelOrder($order, $request->user(), $cancelData['reason']));
        }

        $order->update(['status' => $next]);

        return back()->with('success', 'Pesanan '.$order->code.' diperbarui menjadi '.$order->statusLabel().'.');
    }

    /**
     * Tombol "Bayar": muat item pesanan ke keranjang POS dengan harga sesuai
     * saat pesan, lalu arahkan kasir ke POS untuk memproses pembayaran.
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        if ($order->isPaid() || $order->status !== Order::STATUS_PENDING) {
            return back()->with('error', 'Pesanan '.$order->code.' tidak sedang menunggu pembayaran.');
        }

        // Backup keranjang POS saat ini (jika ada), lalu kosongkan
        $this->cart->backup();

        $unavailable = $order->items->filter(fn ($item) => $item->menu_id === null);

        if ($unavailable->isNotEmpty()) {
            $this->cart->discardBackup();

            return back()->with('error', 'Beberapa item pada '.$order->code.' sudah tidak tersedia. Batalkan pesanan ini lalu buat pesanan manual di kasir.');
        }

        $this->cart->clear();

        foreach ($order->items as $item) {
            $this->cart->addSnapshot(
                $item->menu_id,
                $item->menu_name,
                (float) $item->price,
                (int) $item->quantity
            );
        }

        session(['pos_order_id' => $order->id]);

        return redirect()
            ->route('pos.index')
            ->with('success', 'Item pesanan '.$order->code.' dimuat ke kasir. Proses pembayaran di halaman kasir. Keranjang sebelumnya disimpan dan akan dikembalikan setelah selesai.');
    }

    /**
     * Batalkan muatan pembayaran yang belum diproses dari baner POS.
     * Restore keranjang POS sebelumnya jika ada backup.
     */
    public function payAbort(): RedirectResponse
    {
        $this->cart->clear();
        $this->cart->restore();
        session()->forget('pos_order_id');

        return redirect()
            ->route('orders.index')
            ->with('success', 'Pembayaran pesanan dibatalkan. Keranjang kasir sebelumnya dikembalikan.');
    }

    /**
     * Batalkan pesanan. Jika sudah dibayar, transaksi terkait ikut di-void.
     */
    private function cancelOrder(Order $order, ?User $user, string $reason): string
    {
        $wasPaid = $order->isPaid();
        $reason = trim($reason);

        DB::transaction(function () use ($order, $user, $reason) {
            if ($order->transaction_id) {
                $transaction = Transaction::query()
                    ->where('id', $order->transaction_id)
                    ->lockForUpdate()
                    ->first();

                if ($transaction && $transaction->status !== Transaction::STATUS_VOID) {
                    $this->transactionService->void(
                        $transaction,
                        $user,
                        'Pembatalan pesanan '.$order->code.': '.$reason
                    );
                }
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancelled_by' => $user?->id,
                'cancelled_at' => now(),
            ]);
        });

        return $wasPaid
            ? 'Pesanan '.$order->code.' dibatalkan. Transaksi terkait ikut di-void.'
            : 'Pesanan '.$order->code.' dibatalkan.';
    }
}
