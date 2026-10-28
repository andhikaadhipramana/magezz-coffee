@extends('layouts.app')

@section('title', 'Shift Kasir')
@section('subtitle', 'Buka tutup kas dan rekonsiliasi uang tunai')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                @if($isManager)
                    <div class="col-md-3">
                        <label class="form-label small">Karyawan</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">Semua karyawan</option>
                            @foreach($users as $employee)
                                <option value="{{ $employee->id }}" @selected((string) request('user_id') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small">Tanggal Mulai</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        <option value="open" @selected(request('status') === 'open')>Berjalan</option>
                        <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-brand">Filter</button>
                    @if(request()->filled('user_id') || request()->filled('date') || request()->filled('status'))
                        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-light border">Reset</a>
                    @endif
                </div>
            </form>

            @unless($isManager)
                <div class="small text-muted mt-2">
                    <i class="bi bi-lock me-1"></i>Ringkasan dan riwayat di halaman ini hanya menghitung shift milik Anda.
                </div>
            @endunless

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">{{ $isManager ? 'Jumlah Shift' : 'Jumlah Shift Saya' }}</div>
                        <div class="fw-semibold fs-5">{{ $summary->total ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">{{ $isManager ? 'Total Selisih Kas' : 'Total Selisih Kas Saya' }}</div>
                        <div class="fw-semibold fs-5 {{ abs((float) ($summary->difference ?? 0)) < 0.01 ? 'text-success' : ((float) ($summary->difference ?? 0) > 0 ? 'text-warning' : 'text-danger') }}">
                            {{ rupiah($summary->difference ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($activeShift)
        <div class="card mb-3 border-success">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge badge-soft-success mb-2">Shift Sedang Berjalan</span>
                        <h5 class="mb-1">Shift #{{ $activeShift->id }} &middot; {{ $activeShift->user?->name }}</h5>
                        <small class="text-muted">
                            Dibuka {{ $activeShift->started_at->format('d/m/Y H:i') }}
                            ({{ $activeShift->started_at->diffForHumans() }})
                        </small>
                        <div class="row g-3 mt-2">
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Kas Awal</div>
                                <div class="fw-semibold">{{ rupiah($activeShift->opening_cash) }}</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Penjualan Tunai</div>
                                <div class="fw-semibold">{{ rupiah($activeShift->cashSalesTotal()) }}</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Kas Seharusnya</div>
                                <div class="fw-semibold text-success">
                                    {{ rupiah((float) $activeShift->opening_cash + $activeShift->cashSalesTotal()) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        @if($activeShift->user_id === auth()->id() || auth()->user()->isManager())
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#closeShift">
                                <i class="bi bi-box-arrow-right me-1"></i> Tutup Shift
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <i class="bi bi-clock fs-1 text-muted d-block mb-2"></i>
                <h6>Anda belum membuka shift</h6>
                <p class="text-muted small mb-3">Buka shift untuk mencatat transaksi dan melakukan rekonsiliasi kas.</p>
                <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#openShift">
                    <i class="bi bi-play-circle me-1"></i> Buka Shift Sekarang
                </button>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-list-ul me-1"></i> Riwayat Shift
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mz-table-cards">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kasir</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Ditutup Oleh</th>
                        <th class="text-end">Kas Awal</th>
                        <th class="text-end">Kas Seharusnya</th>
                        <th class="text-end">Kas Aktual</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td class="fw-semibold">#{{ $shift->id }}</td>
                            <td>{{ $shift->user?->name }}</td>
                            <td class="small">{{ $shift->started_at?->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $shift->ended_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="small">{{ $shift->closedBy?->name ?? '-' }}</td>
                            <td class="text-end">{{ rupiah($shift->opening_cash) }}</td>
                            <td class="text-end">{{ $shift->expected_cash !== null ? rupiah($shift->expected_cash) : '-' }}</td>
                            <td class="text-end">{{ $shift->closing_cash !== null ? rupiah($shift->closing_cash) : '-' }}</td>
                            <td class="text-end fw-semibold">
                                @if($shift->cash_difference === null)
                                    <span class="text-muted">-</span>
                                @elseif((float) $shift->cash_difference == 0.0)
                                    <span class="text-success">Sesuai</span>
                                @else
                                    <span class="{{ (float) $shift->cash_difference > 0 ? 'text-warning' : 'text-danger' }}">
                                        {{ (float) $shift->cash_difference > 0 ? '+' : '' }}{{ rupiah($shift->cash_difference) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($shift->status === 'open')
                                    <span class="badge badge-soft-success">Berjalan</span>
                                @else
                                    <span class="badge bg-secondary">Ditutup</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-light border" title="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($shift->isOpen() && ($isManager || $shift->user_id === auth()->id()))
                                    <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-danger">
                                        <i class="bi bi-box-arrow-right"></i> Tutup
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">Belum ada shift.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($shifts->hasPages())
            <div class="card-footer bg-white">{{ $shifts->links() }}</div>
        @endif
    </div>

    {{-- Modal Buka Shift --}}
    @if(! $activeShift)
        <div class="modal fade" id="openShift" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('shifts.open') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">Buka Shift</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kas Awal / Modal Awal Laci (Rp)</label>
                            <input type="number" name="opening_cash" class="form-control" value="0" min="0" required>
                            <small class="text-muted">Jumlah uang tunai yang ada di laci saat shift dimulai.</small>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-brand">Buka Shift</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Tutup Shift --}}
    @if($activeShift)
        <div class="modal fade" id="closeShift" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('shifts.close', $activeShift) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">Tutup Shift #{{ $activeShift->id }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm">
                            <tr><td class="text-muted">Kas Awal</td><td class="text-end">{{ rupiah($activeShift->opening_cash) }}</td></tr>
                            <tr><td class="text-muted">Penjualan Tunai</td><td class="text-end">{{ rupiah($activeShift->cashSalesTotal()) }}</td></tr>
                            <tr class="border-top">
                                <th>Kas Seharusnya</th>
                                <th class="text-end">{{ rupiah((float) $activeShift->opening_cash + $activeShift->cashSalesTotal()) }}</th>
                            </tr>
                        </table>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kas Fisik Aktual di Laci (Rp)</label>
                            <input type="number" name="closing_cash" class="form-control"
                                   value="{{ (int) ((float) $activeShift->opening_cash + $activeShift->cashSalesTotal()) }}"
                                   min="0" required>
                            <small class="text-muted">Hitung uang tunai yang benar-benar ada di laci.</small>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-danger">Tutup Shift</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
