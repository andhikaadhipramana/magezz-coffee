@extends('layouts.app')

@section('title', 'Detail Transaksi')
@section('subtitle', $transaction->code)

@section('content')
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="btn btn-sm btn-brand">
            <i class="bi bi-printer me-1"></i> Cetak Struk
        </a>
        @if($transaction->status !== 'void' && auth()->user()->isManager())
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                <i class="bi bi-x-circle me-1"></i> Void Transaksi
            </button>
        @endif
    </div>

    @if($transaction->status === 'void')
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Transaksi ini telah dibatalkan (void).</strong>
            @if($transaction->void_reason)
                <div class="mt-1 small">Alasan: {{ $transaction->void_reason }}</div>
            @endif
            @if($transaction->voidedBy)
                <div class="small">
                    Dibatalkan oleh {{ $transaction->voidedBy->name }}
                    @if($transaction->voided_at)
                        pada {{ $transaction->voided_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-basket me-1"></i> Item Pesanan
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Menu</th>
                                <th>Varian</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaction->items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->menu_name }}</td>
                                    <td class="small text-muted">{{ $item->variant_info ?: '-' }}</td>
                                    <td class="text-end">{{ rupiah($item->price) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end fw-semibold">{{ rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end text-muted">Subtotal</td>
                                <td class="text-end">{{ rupiah($transaction->subtotal) }}</td>
                            </tr>
                            @if((float) $transaction->discount_amount > 0)
                                <tr>
                                    <td colspan="4" class="text-end text-muted">Diskon</td>
                                    <td class="text-end text-danger">- {{ rupiah($transaction->discount_amount) }}</td>
                                </tr>
                            @endif
                            @if((float) $transaction->tax_amount > 0)
                                <tr>
                                    <td colspan="4" class="text-end text-muted">Pajak PB1</td>
                                    <td class="text-end">{{ rupiah($transaction->tax_amount) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="text-end fw-bold">TOTAL</td>
                                <td class="text-end fw-bold fs-6">{{ rupiah($transaction->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-info-circle me-1"></i> Informasi Transaksi
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Kode</td><td class="text-end fw-semibold">{{ $transaction->code }}</td></tr>
                        <tr><td class="text-muted">Waktu</td><td class="text-end">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td></tr>
                        <tr><td class="text-muted">Kasir</td><td class="text-end">{{ $transaction->user?->name }}</td></tr>
                        <tr><td class="text-muted">Shift</td><td class="text-end">{{ $transaction->shift ? '#' . $transaction->shift->id : '-' }}</td></tr>
                        <tr><td class="text-muted">Metode</td><td class="text-end">{{ $transaction->paymentLabel() }}</td></tr>
                        <tr><td class="text-muted">Dibayar</td><td class="text-end">{{ rupiah($transaction->paid_amount) }}</td></tr>
                        <tr><td class="text-muted">Kembalian</td><td class="text-end">{{ rupiah($transaction->change_amount) }}</td></tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td class="text-end">
                                @if($transaction->status === 'void')
                                    <span class="badge badge-soft-danger">Void</span>
                                @else
                                    <span class="badge badge-soft-success">Selesai</span>
                                @endif
                            </td>
                        </tr>
                        @if($transaction->status === 'void')
                            <tr>
                                <td class="text-muted">Alasan Void</td>
                                <td class="text-end">{{ $transaction->void_reason ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dibatalkan Oleh</td>
                                <td class="text-end">
                                    {{ $transaction->voidedBy?->name ?: '-' }}
                                    @if($transaction->voided_at)
                                        <span class="d-block small text-muted">{{ $transaction->voided_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                    @if($transaction->notes)
                        <hr>
                        <small class="text-muted d-block">Catatan:</small>
                        <div class="small">{{ $transaction->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($transaction->status !== 'void' && auth()->user()->isManager())
        <div class="modal fade" id="voidModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('transactions.void', $transaction) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">Void Transaksi {{ $transaction->code }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small mb-3">
                            Transaksi akan ditandai <strong>void</strong>.
                            Tindakan ini tidak dapat dibatalkan.
                        </div>
                        <label class="form-label small fw-semibold">Alasan Void</label>
                        <input type="text" name="reason" class="form-control form-control-sm"
                               placeholder="Contoh: Salah input pesanan" maxlength="255" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-danger btn-sm">Konfirmasi Void</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
