@extends('layouts.app')

@section('title', 'Manajemen Menu')
@section('subtitle', 'Kelola daftar produk dan harga menu')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="row g-2 flex-grow-1 me-3" style="max-width: 640px">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cari nama / kode menu..." value="{{ request('search') }}">
            </div>
            <div class="col-sm-4">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-sm btn-brand w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
        </form>
        <button class="btn btn-brand btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createMenu">
            <i class="bi bi-plus-lg me-1"></i> Menu Baru
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mz-table-cards">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td class="fw-semibold">{{ $menu->code }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mz-table-thumb">
                                        @if($menu->image_url)
                                            <img src="{{ $menu->image_url }}"
                                                 @if($menu->image_srcset)
                                                     srcset="{{ $menu->image_srcset }}"
                                                     sizes="38px"
                                                 @endif
                                                 alt="Foto {{ $menu->name }}" loading="lazy" decoding="async" data-mz-img>
                                        @else
                                            <i class="bi bi-cup-hot"></i>
                                        @endif
                                    </span>
                                    <span>
                                        {{ $menu->name }}
                                        @if($menu->is_signature)
                                            <span class="badge badge-soft-warning ms-1">Signature</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="text-muted small">{{ $menu->category?->name }}</td>
                            <td class="text-end fw-semibold">{{ rupiah($menu->price) }}</td>
                            <td class="text-center">
                                @if($menu->is_active)
                                    <span class="badge badge-soft-success">Aktif</span>
                                @else
                                    <span class="badge badge-soft-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal"
                                        data-bs-target="#editMenu{{ $menu->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($menu->is_active)
                                    <form method="POST" action="{{ route('menus.destroy', $menu) }}" class="d-inline"
                                          data-mz-confirm="Nonaktifkan menu {{ $menu->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-slash-circle"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada menu ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($menus->hasPages())
            <div class="card-footer bg-white">{{ $menus->links() }}</div>
        @endif
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="createMenu" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('menus.store') }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Menu Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('menus._form', ['menu' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-brand btn-sm">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit --}}
    @foreach($menus as $menu)
        <div class="modal fade" id="editMenu{{ $menu->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="{{ route('menus.update', $menu) }}" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title">Ubah Menu: {{ $menu->name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('menus._form', ['menu' => $menu])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-brand btn-sm">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            const preview = input.closest('.mz-image-field')?.querySelector('[data-image-preview]');

            if (!file || !preview) {
                return;
            }

            const image = document.createElement('img');
            image.alt = 'Pratinjau foto menu';
            image.src = URL.createObjectURL(file);
            preview.replaceChildren(image);
        });
    });
</script>
@endpush
