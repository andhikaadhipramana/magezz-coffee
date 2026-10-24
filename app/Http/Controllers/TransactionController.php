<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): View
    {
        $transactions = Transaction::query()
            ->with(['user', 'items'])
            ->when(! $request->user()->isManager(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->search.'%'))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $this->ensureCanView($request, $transaction);

        $transaction->load(['user', 'shift', 'items', 'voidedBy']);

        return view('transactions.show', compact('transaction'));
    }

    public function receipt(Request $request, Transaction $transaction): View
    {
        $this->ensureCanView($request, $transaction);

        $transaction->load(['user', 'items']);

        return view('transactions.receipt', compact('transaction'));
    }

    /**
     * Kasir hanya boleh melihat transaksi miliknya sendiri.
     */
    private function ensureCanView(Request $request, Transaction $transaction): void
    {
        $user = $request->user();

        if (! $user->isManager() && $transaction->user_id !== $user->id) {
            abort(403, 'Anda hanya dapat melihat transaksi milik Anda.');
        }
    }

    public function void(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ]);

        if ($transaction->status === Transaction::STATUS_VOID) {
            return back()->with('error', 'Transaksi ini sudah dibatalkan.');
        }

        $this->transactionService->void($transaction, $request->user(), $data['reason']);

        return back()->with('success', 'Transaksi '.$transaction->code.' berhasil dibatalkan.');
    }
}
