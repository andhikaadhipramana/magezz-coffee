<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Kode Menu</label>
        <input type="text" name="code" class="form-control form-control-sm"
               value="{{ old('code', $menu?->code) }}" placeholder="MNU-029" required>
    </div>
    <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Menu</label>
        <input type="text" name="name" class="form-control form-control-sm"
               value="{{ old('name', $menu?->name) }}" placeholder="Contoh: Kopi Tubruk" required>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Kategori</label>
        <select name="category_id" class="form-select form-select-sm" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $menu?->category_id) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Harga Dasar (Rp)</label>
        <input type="number" name="price" class="form-control form-control-sm"
               value="{{ old('price', $menu ? (int) $menu->price : '') }}" min="0" required>
    </div>
    <div class="col-12">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional">{{ old('description', $menu?->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Foto Menu</label>
        <div class="mz-image-field">
            <div class="mz-image-preview" data-image-preview>
                @if($menu?->image_url)
                    <img src="{{ $menu->image_url }}" alt="Foto {{ $menu->name }}">
                @else
                    <i class="bi bi-image"></i>
                @endif
            </div>
            <div class="flex-grow-1">
                <input type="file" name="image" class="form-control form-control-sm"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       data-image-input>
                <small class="text-muted d-block mt-1">JPG/PNG/WebP, maksimal 2 MB. Rasio 4:3 disarankan.</small>

                @if($menu?->image_path)
                    <div class="form-check mt-2">
                        <input type="checkbox" name="remove_image" value="1" class="form-check-input"
                               id="removeImage{{ $menu->id }}" @checked(old('remove_image'))>
                        <label class="form-check-label small" for="removeImage{{ $menu->id }}">
                            Hapus foto saat ini
                        </label>
                    </div>
                @endif

                @error('image')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-check">
            <input type="checkbox" name="is_signature" value="1" class="form-check-input"
                   id="sig{{ $menu?->id ?? 'new' }}" @checked(old('is_signature', $menu?->is_signature))>
            <label class="form-check-label small" for="sig{{ $menu?->id ?? 'new' }}">Tandai sebagai menu signature</label>
        </div>
    </div>
    @if($menu)
        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       id="act{{ $menu->id }}" @checked(old('is_active', $menu->is_active))>
                <label class="form-check-label small" for="act{{ $menu->id }}">Menu aktif (tampil di katalog & kasir)</label>
            </div>
        </div>
    @endif
</div>
