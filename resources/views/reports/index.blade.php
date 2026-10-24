@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('subtitle', 'Analisis penjualan periode ' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'))

@section('content')
    <div class="card mb-3">
        <div class="card-body pb-2">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Metode Bayar</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="cash" @selected(request('payment_method') === 'cash')>Tunai</option>
                        <option value="qris" @selected(request('payment_method') === 'qris')>QRIS</option>
                        <option value="card" @selected(request('payment_method') === 'card')>Kartu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Kasir</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1">
                    <button class="btn btn-sm btn-brand flex-grow-1"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                    <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="mz-kpi h-100">
                <div class="mz-kpi__top">
                    <span class="mz-kpi__label">Total Omzet</span>
                    <span class="mz-kpi__icon mz-kpi__icon--dark"><i class="bi bi-cash-stack"></i></span>
                </div>
                <div class="mz-kpi__value">{{ rupiah($summary->revenue) }}</div>
                <div class="mz-kpi__hint">Periode laporan</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="mz-kpi h-100">
                <div class="mz-kpi__top">
                    <span class="mz-kpi__label">Jumlah Transaksi</span>
                    <span class="mz-kpi__icon"><i class="bi bi-receipt"></i></span>
                </div>
                <div class="mz-kpi__value">{{ number_format($summary->trx_count, 0, ',', '.') }}</div>
                <div class="mz-kpi__hint">{{ number_format($itemsSold, 0, ',', '.') }} item terjual</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="mz-kpi h-100">
                <div class="mz-kpi__top">
                    <span class="mz-kpi__label">Total Diskon</span>
                    <span class="mz-kpi__icon"><i class="bi bi-percent"></i></span>
                </div>
                <div class="mz-kpi__value">{{ rupiah($summary->discount) }}</div>
                <div class="mz-kpi__hint">Diskon transaksi</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="mz-kpi h-100">
                <div class="mz-kpi__top">
                    <span class="mz-kpi__label">Total Pajak (PB1)</span>
                    <span class="mz-kpi__icon mz-kpi__icon--sage"><i class="bi bi-bank"></i></span>
                </div>
                <div class="mz-kpi__value">{{ rupiah($summary->tax) }}</div>
                <div class="mz-kpi__hint">Pajak penjualan</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-graph-up me-1"></i> Penjualan Harian
                </div>
                <div class="card-body">
                    @if($daily->isEmpty())
                        <div class="text-center text-muted py-5">Tidak ada data pada periode ini.</div>
                    @else
                        <canvas id="dailyChart" height="120"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-credit-card me-1"></i> Berdasarkan Metode Pembayaran
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Metode</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byPayment as $payment)
                                <tr>
                                    <td>
                                        @php
                                            $label = match($payment->payment_method) {
                                                'cash' => 'Tunai', 'qris' => 'QRIS', 'card' => 'Kartu', default => strtoupper($payment->payment_method)
                                            };
                                        @endphp
                                        <span class="badge badge-soft-info">{{ $label }}</span>
                                    </td>
                                    <td class="text-center">{{ $payment->trx_count }}</td>
                                    <td class="text-end fw-semibold">{{ rupiah($payment->revenue) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-trophy me-1"></i> Produk Terlaris
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">Rank</th>
                                <th>Nama Menu</th>
                                <th class="text-center">Jumlah Terjual</th>
                                <th class="text-end">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bestSellers as $index => $item)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $index + 1 }}</span></td>
                                    <td class="fw-semibold">{{ $item->menu_name }}</td>
                                    <td class="text-center">{{ number_format($item->total_qty, 0, ',', '.') }} porsi</td>
                                    <td class="text-end fw-semibold">{{ rupiah($item->total_revenue) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada penjualan pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if($daily->isNotEmpty())
<script>
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: @json($daily->pluck('tanggal')),
            datasets: [{
                label: 'Omzet (Rp)',
                data: @json($daily->pluck('revenue')),
                borderColor: '#6f4e37',
                backgroundColor: 'rgba(111, 78, 55, .12)',
                fill: true,
                tension: .3,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: (c) => 'Rp ' + Number(c.raw).toLocaleString('id-ID') }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID') }
                }
            }
        }
    });
</script>
@endif
@endpush
