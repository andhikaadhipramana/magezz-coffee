@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', $isManager ? 'Ringkasan operasional Magezz Coffee hari ini' : 'Ringkasan penjualan milik Anda hari ini')

@section('content')
    <div class="mz-kpi-grid">
        <div class="mz-kpi">
            <div class="mz-kpi__top">
                <span class="mz-kpi__label">{{ $isManager ? 'Omzet Hari Ini' : 'Omzet Saya Hari Ini' }}</span>
                <span class="mz-kpi__icon mz-kpi__icon--dark"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="mz-kpi__value">{{ rupiah($todayRevenue) }}</div>
            <div class="mz-kpi__hint">{{ $todayTrx }} transaksi</div>
        </div>

        <div class="mz-kpi">
            <div class="mz-kpi__top">
                <span class="mz-kpi__label">{{ $isManager ? 'Omzet Bulan Ini' : 'Omzet Saya Bulan Ini' }}</span>
                <span class="mz-kpi__icon"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="mz-kpi__value">{{ rupiah($monthRevenue) }}</div>
            <div class="mz-kpi__hint">{{ now()->translatedFormat('F Y') }}</div>
        </div>

        <div class="mz-kpi">
            <div class="mz-kpi__top">
                <span class="mz-kpi__label">{{ $isManager ? 'Item Terjual Hari Ini' : 'Item Terjual (Saya) Hari Ini' }}</span>
                <span class="mz-kpi__icon mz-kpi__icon--sage"><i class="bi bi-bag-check"></i></span>
            </div>
            <div class="mz-kpi__value">{{ number_format($itemsToday, 0, ',', '.') }}</div>
            <div class="mz-kpi__hint">dari {{ $menuCount }} menu aktif</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <span><i class="bi bi-bar-chart-line me-1"></i> {{ $isManager ? 'Penjualan 7 Hari Terakhir' : 'Penjualan Saya 7 Hari Terakhir' }}</span>
                    @if($isManager)
                        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light border">Lihat Laporan</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mz-chart">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <span><i class="bi bi-trophy me-1"></i> {{ $isManager ? 'Produk Terlaris' : 'Produk Terlaris Saya' }}</span>
                </div>
                <div class="card-body py-2">
                    @forelse($bestSellers as $index => $item)
                        <div class="mz-rank">
                            <span class="mz-rank__no">{{ $index + 1 }}</span>
                            <span class="mz-rank__name">{{ $item->menu_name }}</span>
                            <span class="mz-rank__qty">{{ $item->total_qty }} porsi</span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4" style="font-size:13px">
                            Belum ada penjualan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-clock-history me-1"></i> {{ $isManager ? 'Transaksi Terbaru' : 'Transaksi Saya Terbaru' }}</span>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-secondary">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Waktu</th>
                                <th>Kasir</th>
                                <th>Metode</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $trx)
                                <tr>
                                    <td class="fw-semibold">{{ $trx->code }}</td>
                                    <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $trx->user?->name }}</td>
                                    <td>
                                        <span class="badge {{ $trx->status === \App\Models\Transaction::STATUS_VOID ? 'badge-soft-danger' : 'badge-soft-info' }}">
                                            {{ $trx->paymentLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">{{ rupiah($trx->total) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('transactions.show', $trx) }}" class="btn btn-sm btn-light border">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function renderSalesChart() {
        const ctx = document.getElementById('salesChart');

        new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($weeklySales->pluck('label')),
            datasets: [{
                label: 'Omzet (Rp)',
                data: @json($weeklySales->pluck('total')),
                backgroundColor: '#c08a4e',
                hoverBackgroundColor: '#a9743f',
                borderRadius: 8,
                maxBarThickness: 38,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c120d',
                    padding: 12,
                    displayColors: false,
                    titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                    callbacks: {
                        label: (c) => 'Rp ' + Number(c.raw).toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#7a6a5d',
                        font: { family: 'Plus Jakarta Sans', size: 11 }
                    },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(36, 27, 22, .07)' },
                    border: { display: false },
                    ticks: {
                        color: '#7a6a5d',
                        font: { family: 'Plus Jakarta Sans', size: 11 },
                        callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID')
                    }
                }
            }
        }
        });
    }

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(renderSalesChart);
    } else {
        renderSalesChart();
    }
</script>
@endpush
