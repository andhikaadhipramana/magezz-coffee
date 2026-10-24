@extends('layouts.app')

@section('title', 'Detail Shift')
@section('subtitle', 'Shift #' . $shift->id . ' - ' . $shift->user?->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="label">Kas Awal</div>
                    <div class="value">{{ rupiah($shift->opening_cash) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100" style="border-left-color:#198754">
                <div class="card-body">
                    <div class="label">Kas Seharusnya</div>
                    <div class="value">{{ $shift->expected_cash !== null ? rupiah($shift->expected_cash) : '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100" style="border-left-color:#0d6efd">
                <div class="card-body">
                    <div class="label">Kas Fisik Aktual</div>
                    <div class="value">{{ $shift->closing_cash !== null ? rupiah($shift->closing_cash) : '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100" style="border-left-color:{{ (float) $shift->cash_difference < 0 ? '#dc3545' : '#ffc107' }}">
                <div class="card-body">
                    <div class="label">Selisih Kas</div>
                    <div class="value">
                        @if($shift->cash_difference === null)
                            -
                        @elseif((float) $shift->cash_difference == 0.0)
                            <span class="text-success">Sesuai</span>
                        @else
                            {{ rupiah($shift->cash_difference) }}
                        @endif
                    </div>
                    @if($shift->cash_difference !== null && (float) $shift->cash_difference != 0.0)
                        <small class="text-muted">{{ (float) $shift->cash_difference > 0 ? 'Surplus' : 'Defisit' }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-info-circle me-1"></i> Informasi Shift
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Kasir</td><td class="text-end">{{ $shift->user?->name }}</td></tr>
                        <tr><td class="text-muted">Ditutup Oleh</td><td class="text-end">{{ $shift->closedBy?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Mulai</td><td class="text-end">{{ $shift->started_at?->format('d/m/Y H:i') }}</td></tr>
                        <tr><td class="text-muted">Selesai</td><td class="text-end">{{ $shift->ended_at?->format('d/m/Y H:i') ?? 'Masih berjalan' }}</td></tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td class="text-end">
                                @if($shift->isOpen())
                                    <span class="badge badge-soft-success">Berjalan</span>
                                @else
                                    <span class="badge bg-secondary">Ditutup</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                    @if($shift->notes)
                        <hr>
                        <small class="text-muted d-block">Catatan:</small>
                        <div class="small">{{ $shift->notes }}</div>
                    @endif
                </div>
            </div>

            @if($shift->isOpen() && (auth()->user()->isManager() || $shift->user_id === auth()->id()))
                @php $expected = (float) $shift->opening_cash + $shift->cashSalesTotal(); @endphp
                <div class="card mt-3 border-danger">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-box-arrow-right me-1"></i> Tutup Shift
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shifts.close', $shift) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Kas Fisik Aktual di Laci (Rp)</label>
                                <input type="number" name="closing_cash" class="form-control" value="{{ old('closing_cash', (int) $expected) }}" min="0" required>
                                <small class="text-muted">Kas seharusnya: {{ rupiah($expected) }}.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Catatan</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Wajib diisi jika ada selisih kas" maxlength="255">
                            </div>
                            <button class="btn btn-danger w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> Tutup Shift #{{ $shift->id }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-receipt me-1"></i> Transaksi pada Shift Ini
                    <span class="badge bg-secondary ms-1">{{ $shift->transactions->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Waktu</th>
                                <th>Metode</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shift->transactions->where('status', 'completed') as $trx)
                                <tr>
                                    <td class="fw-semibold small">{{ $trx->code }}</td>
                                    <td class="small">{{ $trx->created_at->format('H:i') }}</td>
                                    <td><span class="badge badge-soft-info">{{ $trx->paymentLabel() }}</span></td>
                                    <td class="text-end">{{ rupiah($trx->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada transaksi pada shift ini.</td></tr>
                            @endforelse
                        </tbody>
                        @if($shift->transactions->where('status', 'completed')->count() > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Shift</th>
                                    <th class="text-end">{{ rupiah($shift->transactions->where('status', 'completed')->sum('total')) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
