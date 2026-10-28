@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('subtitle', 'Semua transaksi penjualan')

@section('content')
    <div class="card mb-3">
        <div class="card-body pb-2">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Cari kode transaksi..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        <option value="cash" @selected(request('payment_method') === 'cash')>Tunai</option>
                        <option value="qris" @selected(request('payment_method') === 'qris')>QRIS</option>
                        <option value="card" @selected(request('payment_method') === 'card')>Kartu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                        <option value="void" @selected(request('status') === 'void')>Void</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-sm btn-brand"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mz-table-cards">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th class="text-center">Item</th>
                        <th>Metode</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr class="{{ $trx->status === 'void' ? 'opacity-75' : '' }}">
                            <td class="fw-semibold {{ $trx->status === 'void' ? 'text-decoration-line-through' : '' }}">{{ $trx->code }}</td>
                            <td class="small">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $trx->user?->name }}</td>
                            <td class="text-center">{{ $trx->items->sum('quantity') }}</td>
                            <td><span class="badge badge-soft-info">{{ $trx->paymentLabel() }}</span></td>
                            <td class="text-end fw-semibold">{{ rupiah($trx->total) }}</td>
                            <td class="text-center">
                                @if($trx->status === 'void')
                                    <span class="badge badge-soft-danger">Void</span>
                                @else
                                    <span class="badge badge-soft-success">Selesai</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('transactions.show', $trx) }}" class="btn btn-sm btn-light border">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('transactions.receipt', $trx) }}" target="_blank"
                                   class="btn btn-sm btn-light border">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-white">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection
