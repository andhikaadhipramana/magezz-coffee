@extends('layouts.public')

@section('title', 'Pesanan ' . $order->code)
@section('brand', $outletName)

@push('meta')
    <meta name="robots" content="noindex,nofollow">
@endpush

@if(! $order->isFinished())
    @push('meta')
        <meta http-equiv="refresh" content="15">
    @endpush
@endif

@section('content')
    <div class="mz-page">
        <div class="mz-container" style="max-width:980px">
            <div class="mz-page-head" style="margin-bottom:18px">
                <div>
                    <div class="mz-eyebrow">Status Pesanan</div>
                    <h1 class="mz-display" style="font-size:clamp(1.8rem,3vw,2.3rem);margin-top:8px">Terima kasih sudah mampir.</h1>
                </div>
                <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--outline mz-btn--sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Menu
                </a>
            </div>

            <div class="mz-status-grid">
                <div class="mz-status-grid__main">
                    <div class="mz-status-hero mz-grain">
                        <div class="mz-order-code">
                            <i class="bi bi-receipt"></i> {{ $order->code }}
                        </div>
                        <h1>
                            @if($order->status === \App\Models\Order::STATUS_CANCELLED)
                                Pesanan dibatalkan
                            @elseif($order->status === \App\Models\Order::STATUS_COMPLETED)
                                Pesanan selesai
                            @elseif($order->isPaid())
                                Sedang disiapkan
                            @else
                                Menunggu pembayaran
                            @endif
                        </h1>
                        <p>
                            Dibuat {{ $order->createdAtLabel() }} WIB
                            @if($order->table_number)
                                &middot; Meja {{ $order->table_number }}
                            @endif
                        </p>
                        <div class="mz-status-hero__chips">
                            <span class="mz-badge {{ $order->badgeClass() }}">{{ $order->statusLabel() }}</span>
                            @if($order->isPaid())
                                <span class="mz-badge mz-badge--sage"><i class="bi bi-cash-coin"></i> Lunas</span>
                            @else
                                <span class="mz-badge"><i class="bi bi-clock"></i> Belum dibayar</span>
                            @endif
                        </div>
                    </div>

                    @unless($order->status === \App\Models\Order::STATUS_CANCELLED)
                        <div class="mz-panel" style="margin-top:16px">
                            <div class="mz-panel__body">
                                <h2 class="mz-panel__title" style="margin-bottom:18px">Progres pesanan</h2>

                                @php
                                    $currentStep = $order->stepNumber();
                                    $steps = [
                                        1 => ['Pesanan diterima', 'Menunggu pembayaran di kasir'],
                                        2 => ['Dibayar &amp; diproses', 'Barista menyiapkan pesananmu'],
                                        3 => ['Siap diambil', 'Silakan ambil di bar'],
                                        4 => ['Selesai', 'Terima kasih sudah mampir'],
                                    ];
                                @endphp

                                <div class="mz-steps">
                                    @foreach($steps as $step => [$label, $meta])
                                        <div class="mz-step {{ $currentStep > $step ? 'is-done' : ($currentStep === $step ? 'is-active' : '') }}">
                                            <div class="mz-step__dot">
                                                @if($currentStep > $step)
                                                    <i class="bi bi-check2"></i>
                                                @else
                                                    {{ $step }}
                                                @endif
                                            </div>
                                            <div>
                                                <div class="mz-step__label">{!! $label !!}</div>
                                                <div class="mz-step__meta">
                                                    @if($step === 1)
                                                        {{ $order->createdAtLabel() }} WIB
                                                    @elseif($step === 2 && $order->isPaid())
                                                        {{ $order->paid_at?->format('H.i') }} WIB
                                                    @else
                                                        {{ $meta }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(! $order->isFinished())
                                    <div class="mz-note" style="margin-top:4px">
                                        <i class="bi bi-arrow-repeat"></i>
                                        <div>Halaman diperbarui otomatis setiap 15 detik.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endunless

                    @if($order->status === \App\Models\Order::STATUS_CANCELLED)
                        <div class="mz-note" style="margin-top:16px;background:var(--mz-danger-100);color:var(--mz-danger-700)">
                            <i class="bi bi-slash-circle"></i>
                            <div>
                                Pesanan dibatalkan.{{ $order->isPaid() ? ' Pembayaran dikembalikan (transaksi di-void).' : ' Anda belum melakukan pembayaran.' }}
                            </div>
                        </div>
                    @elseif($order->status === \App\Models\Order::STATUS_COMPLETED)
                        <div class="mz-note" style="margin-top:16px">
                            <i class="bi bi-check2-circle"></i>
                            <div>Terima kasih! Pesanan Anda sudah selesai.</div>
                        </div>
                    @elseif($order->isPaid())
                        <div class="mz-note" style="margin-top:16px">
                            <i class="bi bi-cash-coin"></i>
                            <div>
                                Pembayaran diterima{{ $order->transaction ? ' ('.$order->transaction->code.')' : '' }}.
                                Pesanan sedang disiapkan, mohon tunggu.
                            </div>
                        </div>
                    @else
                        <div class="mz-pay-card" style="margin-top:16px">
                            <div class="mz-pay-card__head">
                                <i class="bi bi-cash-coin"></i>
                                <div>
                                    <strong>Pembayaran di kasir</strong>
                                    <small>Tunjukkan kode pesanan ini ke kasir</small>
                                </div>
                            </div>
                            <p style="margin:0;font-size:13px;color:var(--mz-ink-700)">
                                <strong>Belum dibayar.</strong> Tunjukkan kode
                                <strong>{{ $order->code }}</strong> ke kasir untuk melakukan pembayaran.
                            </p>
                        </div>
                    @endif
                </div>

                <aside class="mz-status-grid__side">
                    <div class="mz-summary" style="position:static">
                        <h2 class="mz-summary__title">Rincian Pesanan</h2>

                        @foreach($order->items as $item)
                            <div class="mz-summary__row">
                                <span>{{ $item->menu_name }} &times; {{ $item->quantity }}</span>
                                <strong>{{ rupiah($item->subtotal) }}</strong>
                            </div>
                        @endforeach

                        <hr class="mz-divider">

                        <div class="mz-summary__row">
                            <span>Subtotal</span>
                            <strong>{{ rupiah($order->subtotal) }}</strong>
                        </div>
                        @if($order->hasTax())
                            <div class="mz-summary__row">
                                <span>Pajak PB1</span>
                                <strong>{{ rupiah($order->tax_amount) }}</strong>
                            </div>
                        @endif

                        <div class="mz-summary__total">
                            <span>Total</span>
                            <strong>{{ rupiah($order->total) }}</strong>
                        </div>

                        <small class="mz-muted" style="display:block;margin-top:2px;font-size:11.5px">
                            Total sudah termasuk pajak{{ $order->transaction ? '.' : ' dan menjadi acuan pembayaran di kasir.' }}
                        </small>

                        @if($order->transaction)
                            <div class="mz-summary__row">
                                <span>Dibayar ({{ $order->transaction->paymentLabel() }})</span>
                                <strong>{{ rupiah($order->transaction->total) }}</strong>
                            </div>
                        @endif

                        @if($order->customer_name || $order->table_number || $order->notes)
                            <hr class="mz-divider">
                            <div style="font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--mz-ink-500);margin-bottom:6px">
                                Detail Pemesan
                            </div>
                            <dl class="mb-0">
                                @if($order->customer_name)
                                    <div class="mz-kv"><dt>Nama</dt><dd>{{ $order->customer_name }}</dd></div>
                                @endif
                                @if($order->table_number)
                                    <div class="mz-kv"><dt>Nomor meja</dt><dd>{{ $order->table_number }}</dd></div>
                                @endif
                                @if($order->notes)
                                    <div class="mz-kv"><dt>Catatan</dt><dd>{{ $order->notes }}</dd></div>
                                @endif
                            </dl>
                        @endif

                        <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--outline mz-btn--block" style="margin-top:16px">
                            <i class="bi bi-cup-hot"></i> Pesan Lagi
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
