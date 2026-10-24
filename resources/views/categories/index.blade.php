@extends('layouts.app')

@section('title', 'Kategori Menu')
@section('subtitle', 'Kelompok menu untuk memudahkan pencarian di kasir')

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('categories.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Kode</label>
                            <input type="text" name="code" class="form-control form-control-sm"
                                   placeholder="KAT-06" value="{{ old('code') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nama Kategori</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   placeholder="Contoh: Teh & Herbal" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Deskripsi</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-brand w-100 btn-sm">
                            <i class="bi bi-save me-1"></i> Simpan Kategori
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-tags me-1"></i> Daftar Kategori
                    <span class="badge bg-secondary ms-1">{{ $categories->total() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 mz-table-cards">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Menu</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td class="fw-semibold">{{ $category->code }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td class="text-muted small">{{ $category->description ?: '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-info">{{ $category->menus_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="badge badge-soft-success">Aktif</span>
                                        @else
                                            <span class="badge badge-soft-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal"
                                                data-bs-target="#editCat{{ $category->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                              class="d-inline" data-mz-confirm="Hapus kategori {{ $category->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-light border text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                        <div class="modal fade text-start" id="editCat{{ $category->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <form method="POST" action="{{ route('categories.update', $category) }}" class="modal-content">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Ubah Kategori</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Kode</label>
                                                            <input type="text" name="code" class="form-control form-control-sm"
                                                                   value="{{ $category->code }}" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Nama</label>
                                                            <input type="text" name="name" class="form-control form-control-sm"
                                                                   value="{{ $category->name }}" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Deskripsi</label>
                                                            <textarea name="description" class="form-control form-control-sm" rows="2">{{ $category->description }}</textarea>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                                                   id="catActive{{ $category->id }}" @checked($category->is_active)>
                                                            <label class="form-check-label small" for="catActive{{ $category->id }}">Aktif</label>
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
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($categories->hasPages())
                    <div class="card-footer bg-white">{{ $categories->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
