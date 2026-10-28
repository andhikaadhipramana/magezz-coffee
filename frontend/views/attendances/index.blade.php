@extends('layouts.app')

@section('title', 'Absensi Karyawan')
@section('subtitle', 'Clock-in / clock-out, koreksi, dan rekap harian')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="mb-1"><i class="bi bi-calendar-check me-1"></i> Absensi Manual</h6>
                    <small class="text-muted">Jam masuk dan jam keluar diatur manual sesuai jam kerja kasir yang sedang bertugas.</small>
                </div>
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    @if($isManager)
                        <select name="user_id" class="form-select form-select-sm" style="min-width:180px">
                            <option value="">Semua karyawan</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) request('user_id') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" max="{{ today()->format('Y-m-d') }}">
                    <button class="btn btn-sm btn-light border">Lihat</button>
                    @if(request()->filled('date') || request()->filled('user_id'))
                        <a href="{{ route('attendances.index') }}" class="btn btn-sm btn-light border">Reset</a>
                    @endif
                </form>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Karyawan</div>
                        <div class="fw-semibold mb-2">{{ $selectedUser->name }}</div>
                        <div class="small text-muted">Tanggal</div>
                        <div class="fw-semibold mb-2">{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
                        <div class="small text-muted">Clock-In</div>
                        <div class="fw-semibold fs-5">{{ $selectedAttendance?->clock_in?->format('H:i') ?? '--:--' }}</div>
                        <div class="small text-muted mt-2">Clock-Out</div>
                        <div class="fw-semibold fs-5">{{ $selectedAttendance?->clock_out?->format('H:i') ?? '--:--' }}</div>
                        <div class="small text-muted mt-2">Durasi</div>
                        <div class="fw-semibold fs-5">
                            @php $dur = $selectedAttendance?->durationMinutes(); @endphp
                            {{ $dur !== null ? floor($dur / 60) . ' jam ' . ($dur % 60) . ' mnt' : '-' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    @if($selectedUserId === auth()->id())
                        @if($selectedDate === today()->format('Y-m-d'))
                            <div class="border rounded p-3">
                                <form method="POST" action="{{ route('attendances.clock-in') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="date" value="{{ today()->format('Y-m-d') }}">
                                    <div class="col-md-5">
                                        <label class="form-label small">Jam Masuk Hari Ini</label>
                                        <input type="time" name="clock_in" class="form-control" value="{{ old('clock_in', now()->format('H:i')) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Jam Masuk Kerja
                                        </button>
                                    </div>
                                </form>

                                <hr class="my-3">

                                <form method="POST" action="{{ route('attendances.clock-out') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="date" value="{{ today()->format('Y-m-d') }}">
                                    <div class="col-md-5">
                                        <label class="form-label small">Jam Keluar Hari Ini</label>
                                        <input type="time" name="clock_out" class="form-control" value="{{ old('clock_out', now()->format('H:i')) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-box-arrow-right me-1"></i> Jam Pulang Kerja
                                        </button>
                                    </div>
                                </form>

                                <div class="mt-3 small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Clock-out hanya bisa diisi setelah clock-in pada tanggal yang sama, dan jam keluar tidak boleh lebih awal dari jam masuk.
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Absensi manual hanya untuk hari ini. Untuk tanggal lain, gunakan form koreksi di bawah.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Anda sedang melihat absensi <strong>{{ $selectedUser->name }}</strong>. Gunakan form koreksi di bawah untuk mengubah data.
                        </div>
                    @endif

                    @if($isManager)
                        <div class="border rounded p-3 mt-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <h6 class="mb-1"><i class="bi bi-pencil-square me-1"></i> Koreksi Absensi</h6>
                                    <small class="text-muted">Khusus manager. Isi minimal salah satu jam dan wajib menyertakan alasan.</small>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('attendances.correct') }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label small">Karyawan</label>
                                    <select name="user_id" class="form-select" required>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected($selectedUserId === $employee->id)>
                                                {{ $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Tanggal</label>
                                    <input type="date" name="date" class="form-control" value="{{ old('date', $selectedDate) }}" max="{{ today()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Jam Masuk</label>
                                    <input type="time" name="clock_in" class="form-control" value="{{ old('clock_in', $selectedAttendance?->clock_in?->format('H:i')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Jam Keluar</label>
                                    <input type="time" name="clock_out" class="form-control" value="{{ old('clock_out', $selectedAttendance?->clock_out?->format('H:i')) }}">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label small">Alasan Koreksi</label>
                                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Contoh: salah input jam pulang" maxlength="255" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-brand w-100">
                                        <i class="bi bi-save me-1"></i> Simpan Koreksi
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-clipboard-data me-1"></i> Rekap Operasional</span>
            <span class="text-muted small ms-1">{{ $selectedUser->name }} &middot; {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Durasi Absensi</div>
                        <div class="fw-semibold">{{ $dur !== null ? floor($dur / 60) . ' jam ' . ($dur % 60) . ' mnt' : '-' }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Jumlah Shift</div>
                        <div class="fw-semibold">{{ $selectedShifts->count() }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Transaksi Selesai</div>
                        <div class="fw-semibold">{{ $selectedTransactionCount }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Total Penjualan</div>
                        <div class="fw-semibold">{{ rupiah($selectedTransactionTotal) }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="small text-muted">Total Selisih Kas</div>
                            <div class="fw-semibold {{ abs($selectedCashDifference) < 0.01 ? 'text-success' : ($selectedCashDifference > 0 ? 'text-warning' : 'text-danger') }}">
                                {{ rupiah($selectedCashDifference) }}
                                {{ abs($selectedCashDifference) < 0.01 ? '(sesuai)' : ($selectedCashDifference > 0 ? '(surplus)' : '(defisit)') }}
                            </div>
                        </div>
                        <a href="{{ route('shifts.index', ['date' => $selectedDate, 'user_id' => $selectedUserId]) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-clock-history me-1"></i> Lihat Shift
                        </a>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Shift</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Status</th>
                            <th>Ditutup Oleh</th>
                            <th class="text-end">Selisih Kas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selectedShifts as $shift)
                            <tr>
                                <td class="fw-semibold">#{{ $shift->id }}</td>
                                <td>{{ $shift->started_at?->format('H:i') ?? '-' }}</td>
                                <td>{{ $shift->ended_at?->format('H:i') ?? '-' }}</td>
                                <td>
                                    @if($shift->isOpen())
                                        <span class="badge badge-soft-success">Berjalan</span>
                                    @else
                                        <span class="badge bg-secondary">Ditutup</span>
                                    @endif
                                </td>
                                <td>{{ $shift->closedBy?->name ?? '-' }}</td>
                                <td class="text-end">
                                    @if($shift->cash_difference === null)
                                        -
                                    @else
                                        {{ rupiah($shift->cash_difference) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada shift pada tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($isManager && $corrections->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Riwayat Koreksi Absensi
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 mz-table-cards">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu Koreksi</th>
                            <th>Oleh</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($corrections as $correction)
                            <tr>
                                <td class="small">{{ $correction->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $correction->editor?->name ?? '-' }}</td>
                                <td class="small">
                                    {{ $correction->old_clock_in?->format('H:i') ?? '-' }}
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    {{ $correction->new_clock_in?->format('H:i') ?? '-' }}
                                </td>
                                <td class="small">
                                    {{ $correction->old_clock_out?->format('H:i') ?? '-' }}
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    {{ $correction->new_clock_out?->format('H:i') ?? '-' }}
                                </td>
                                <td class="small text-muted">{{ $correction->reason }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-list-ul me-1"></i> Riwayat Absensi</span>
            <span class="small text-muted">{{ $attendances->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mz-table-cards">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Karyawan</th>
                        <th>Clock-In</th>
                        <th>Clock-Out</th>
                        <th>Durasi Kerja</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        @php $minutes = $attendance->durationMinutes(); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $attendance->date->format('d/m/Y') }}</td>
                            <td>{{ $attendance->user?->name }}</td>
                            <td>{{ $attendance->clock_in?->format('H:i') ?? '-' }}</td>
                            <td>{{ $attendance->clock_out?->format('H:i') ?? '-' }}</td>
                            <td>
                                @if($minutes !== null)
                                    {{ floor($minutes / 60) }} jam {{ $minutes % 60 }} mnt
                                @else
                                    <span class="badge badge-soft-warning">Belum clock-out</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $attendance->notes ?: '-' }}
                                @if($attendance->corrections_count > 0)
                                    <span class="badge badge-soft-info ms-1">Dikoreksi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attendances->hasPages())
            <div class="card-footer bg-white">{{ $attendances->links() }}</div>
        @endif
    </div>
@endsection
