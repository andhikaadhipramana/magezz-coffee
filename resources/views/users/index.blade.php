@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola akun karyawan dan hak akses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted small">
            <i class="bi bi-shield-check me-1"></i>
            Hak akses: <strong>Manager</strong> (semua fitur), <strong>Kasir</strong> (POS, shift & absensi)
        </div>
        <button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#createUser">
            <i class="bi bi-person-plus me-1"></i> Akun Baru
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Peran</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="{{ ! $user->is_active ? 'opacity-75' : '' }}">
                            <td class="fw-semibold">
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span class="badge bg-secondary ms-1">Anda</span>
                                @endif
                            </td>
                            <td class="small">{{ $user->email }}</td>
                            <td class="small">{{ $user->phone ?: '-' }}</td>
                            <td>
                                @php
                                    $roleBadge = match($user->role) {
                                        'manager' => 'badge-soft-info',
                                        default => 'badge-soft-success',
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }}">{{ $user->roleLabel() }}</span>
                            </td>
                            <td class="text-center">
                                @if($user->is_active)
                                    <span class="badge badge-soft-success">Aktif</span>
                                @else
                                    <span class="badge badge-soft-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal"
                                        data-bs-target="#editUser{{ $user->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($user->id !== auth()->id() && $user->is_active)
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                          data-mz-confirm="Nonaktifkan akun {{ $user->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-person-dash"></i></button>
                                    </form>
                                @endif

                                <div class="modal fade text-start" id="editUser{{ $user->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" action="{{ route('users.update', $user) }}" class="modal-content">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h6 class="modal-title">Ubah Akun: {{ $user->name }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                                                        <input type="text" name="name" class="form-control form-control-sm"
                                                               value="{{ $user->name }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Email</label>
                                                        <input type="email" name="email" class="form-control form-control-sm"
                                                               value="{{ $user->email }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Telepon</label>
                                                        <input type="text" name="phone" class="form-control form-control-sm"
                                                               value="{{ $user->phone }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Peran</label>
                                                        <select name="role" class="form-select form-select-sm" required>
                                                            <option value="manager" @selected($user->role === 'manager')>Manager</option>
                                                            <option value="kasir" @selected($user->role === 'kasir')>Kasir / Barista</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Kata Sandi Baru</label>
                                                        <input type="password" name="password" class="form-control form-control-sm"
                                                               placeholder="Kosongkan bila tidak diubah">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                                                   id="uAct{{ $user->id }}" @checked($user->is_active)>
                                                            <label class="form-check-label small" for="uAct{{ $user->id }}">Akun aktif</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button class="btn btn-brand btn-sm">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="createUser" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('users.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Akun Karyawan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Telepon</label>
                            <input type="text" name="phone" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Peran</label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="kasir">Kasir / Barista</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kata Sandi</label>
                            <input type="password" name="password" class="form-control form-control-sm"
                                   placeholder="Min. 6 karakter" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-brand btn-sm">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
@endsection
