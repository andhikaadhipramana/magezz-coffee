@extends('layouts.app')

@section('title', 'Pesanan')
@section('subtitle', 'Pesanan yang dikirim customer dari katalog menu')

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('orders.index') }}" class="btn btn-sm {{ $status === null ? 'btn-brand' : 'btn-outline-secondary' }}">
            Semua <span class="badge ms-1 bg-light text-dark">{{ $counts->sum() }}</span>
        </a>
        @foreach([\App\Models\Order::STATUS_PENDING => 'Menunggu Bayar', \App\Models\Order::STATUS_IN_PROGRESS => 'Diproses', \App\Models\Order::STATUS_READY => 'Siap Diambil', \App\Models\Order::STATUS_COMPLETED => 'Selesai', \App\Models\Order::STATUS_CANCELLED => 'Dibatalkan'] as $key => $label)
            <a href="{{ route('orders.index', ['status' => $key]) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-brand' : 'btn-outline-secondary' }}">
                {{ $label }} <span class="badge ms-1 bg-light text-dark">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    @if($orders->isEmpty())
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <h5 class="mt-3 mb-1">Belum ada pesanan</h5>
            <p class="text-muted mb-0">Pesanan dari katalog menu akan muncul di sini.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($orders as $order)
                <div class="col-12 col-lg-6 col-xxl-4">
                    <div class="card h-100 {{ $order->status === \App\Models\Order::STATUS_PENDING ? 'border-warning' : '' }}">
                        <div class="card-header d-flex justify-content-between align-items-center bg-white">
                            <div>
                                <span class="fw-bold">{{ $order->code }}</span>
                                <small class="text-muted d-block">{{ $order->createdAtLabel() }}</small>
                            </div>
                            <span class="badge {{ $order->badgeClass() }}">{{ $order->statusLabel() }}</span>
                            @if($order->isPaid())
                                <span class="badge badge-soft-success"><i class="bi bi-cash-coin me-1"></i>Lunas{{ $order->transaction ? ' · '.$order->transaction->code : '' }}</span>
                            @endif
                        </div>

                        <div class="card-body py-3">
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                @if($order->table_number)
                                    <span class="badge badge-soft-info"><i class="bi bi-geo me-1"></i>Meja {{ $order->table_number }}</span>
                                @endif
                                @if($order->customer_name)
                                    <span class="badge badge-soft-info"><i class="bi bi-person me-1"></i>{{ $order->customer_name }}</span>
                                @endif
                            </div>

                            @foreach($order->items as $item)
                                <div class="d-flex justify-content-between small py-1 {{ $loop->first ? '' : 'border-top' }}">
                                    <span>{{ $item->quantity }} x {{ $item->menu_name }}</span>
                                    <span class="fw-semibold">{{ rupiah($item->subtotal) }}</span>
                                </div>
                            @endforeach

                            @if($order->notes)
                                <div class="alert alert-warning small py-2 mt-2 mb-0">
                                    <i class="bi bi-chat-left-text me-1"></i>{{ $order->notes }}
                                </div>
                            @endif

                            @if($order->status === \App\Models\Order::STATUS_CANCELLED && $order->cancel_reason)
                                <div class="alert alert-danger small py-2 mt-2 mb-0">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Dibatalkan{{ $order->cancelledBy ? ' oleh '.$order->cancelledBy->name : '' }}: {{ $order->cancel_reason }}
                                    @if($order->cancelled_at)
                                        <span class="d-block" style="font-size:11px">{{ $order->cancelled_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    {{ $order->isPaid() ? 'Total dibayar' : 'Total' }}
                                    @if(! $order->isPaid() && $order->hasTax())
                                        <span class="d-block" style="font-size:11px">Termasuk pajak {{ rupiah($order->tax_amount) }}</span>
                                    @endif
                                </small>
                                <span class="fw-bold">{{ rupiah($order->isPaid() ? ($order->transaction?->total ?? $order->total) : $order->total) }}</span>
                            </div>

                            @if(! $order->isFinished())
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($order->status === \App\Models\Order::STATUS_PENDING)
                                        <form method="POST" action="{{ route('orders.pay', $order) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-brand"><i class="bi bi-cash-coin me-1"></i> Bayar di Kasir</button>
                                        </form>
                                    @endif
                                    @if(in_array(\App\Models\Order::STATUS_READY, $order->nextStatuses(), true))
                                        <form method="POST" action="{{ route('orders.update-status', $order) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_READY }}">
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2 me-1"></i> Siap Diambil</button>
                                        </form>
                                    @endif
                                    @if(in_array(\App\Models\Order::STATUS_COMPLETED, $order->nextStatuses(), true))
                                        <form method="POST" action="{{ route('orders.update-status', $order) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_COMPLETED }}">
                                            <button class="btn btn-sm btn-success"><i class="bi bi-check2-all me-1"></i> Selesai</button>
                                        </form>
                                    @endif
                                    @if(in_array(\App\Models\Order::STATUS_CANCELLED, $order->nextStatuses(), true))
                                        <form method="POST" action="{{ route('orders.update-status', $order) }}"
                                              class="d-flex gap-2 align-items-center flex-wrap"
                                              data-mz-confirm="Batalkan pesanan {{ $order->code }}?{{ $order->isPaid() ? ' Transaksi terkait akan di-void.' : '' }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_CANCELLED }}">
                                            <input type="text" name="reason" class="form-control form-control-sm" style="max-width:230px"
                                                   placeholder="Alasan pembatalan" maxlength="255" required>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x me-1"></i> Batalkan</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">{{ $orders->links() }}</div>
    @endif
@endsection
