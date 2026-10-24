@extends('layouts.public')

@section('title', 'Keranjang')
@section('brand', 'Magezz Coffee')

@push('meta')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
    <div class="mz-page">
        <div class="mz-container">
            <div class="mz-page-head" style="margin-bottom:22px">
                <div>
                    <div class="mz-eyebrow">Pesanan Anda</div>
                    <h1 class="mz-display" style="font-size:clamp(1.8rem,3vw,2.4rem);margin-top:8px">Keranjang Pesanan</h1>
                    <p class="mz-muted" style="margin:6px 0 0;font-size:13px">Periksa pesanan sebelum dikirim ke kasir.</p>
                </div>
                <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--outline mz-btn--sm">
                    <i class="bi bi-arrow-left"></i> Lanjut Pilih Menu
                </a>
            </div>

            @if(empty($items))
                <div class="mz-empty">
                    <i class="bi bi-basket"></i>
                    <strong>Keranjang masih kosong</strong>
                    <p>Pilih menu favorit Anda dari katalog.</p>
                    <div style="margin-top:16px">
                        <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--primary">
                            <i class="bi bi-cup-hot"></i> Lihat Menu
                        </a>
                    </div>
                </div>
            @else
                <div class="mz-cart-grid">
                    <div class="mz-panel">
                        <div class="mz-panel__head">
                            <h2 class="mz-panel__title">{{ count($items) }} menu &middot; {{ $count }} porsi</h2>
                            <form method="POST" action="{{ route('public.cart.clear') }}">
                                @csrf
                                <button class="mz-remove" type="submit">
                                    <i class="bi bi-trash3"></i> Kosongkan
                                </button>
                            </form>
                        </div>

                        @foreach($items as $item)
                            @php
                                $categoryName = \Illuminate\Support\Str::lower($item['menu']->category?->name ?? '');
                                $itemName = \Illuminate\Support\Str::lower($item['menu_name']);
                                $lookup = $categoryName !== '' ? $categoryName : $itemName;
                                $itemImage = $item['menu']->image_url;
                                $itemIcon = match (true) {
                                    str_contains($lookup, 'non-kopi') => 'bi-cup-straw',
                                    str_contains($lookup, 'kopi') => 'bi-cup-hot-fill',
                                    str_contains($lookup, 'teh') => 'bi-cup-straw',
                                    str_contains($lookup, 'makan'), str_contains($lookup, 'roti'),
                                    str_contains($lookup, 'snack'), str_contains($lookup, 'pastry') => 'bi-egg-fried',
                                    str_contains($lookup, 'kue'), str_contains($lookup, 'cake') => 'bi-cake2',
                                    default => 'bi-cup',
                                };
                                $itemTile = match (true) {
                                    str_contains($lookup, 'non-kopi') => 'mz-menu-card__tile--teh',
                                    str_contains($lookup, 'kopi') => 'mz-menu-card__tile--kopi',
                                    str_contains($lookup, 'teh') => 'mz-menu-card__tile--teh',
                                    str_contains($lookup, 'makan'), str_contains($lookup, 'roti'),
                                    str_contains($lookup, 'snack'), str_contains($lookup, 'pastry') => 'mz-menu-card__tile--makanan',
                                    str_contains($lookup, 'kue'), str_contains($lookup, 'cake') => 'mz-menu-card__tile--dessert',
                                    default => 'mz-menu-card__tile--susu',
                                };
                            @endphp

                            <div class="mz-cart-item">
                                <div class="mz-cart-item__thumb {{ $itemImage ? 'has-image' : $itemTile }}" style="height:64px">
                                    @if($itemImage)
                                        <img src="{{ $itemImage }}"
                                             @if($item['menu']->image_srcset)
                                                 srcset="{{ $item['menu']->image_srcset }}"
                                                 sizes="64px"
                                             @endif
                                             alt="Foto {{ $item['menu_name'] }}" loading="lazy" decoding="async" data-mz-img>
                                    @else
                                        <i class="bi {{ $itemIcon }}"></i>
                                    @endif
                                </div>

                                <div>
                                    <p class="mz-cart-item__name">{{ $item['menu_name'] }}</p>
                                    <div class="mz-cart-item__meta">{{ rupiah($item['price']) }} / porsi</div>

                                    <form method="POST" action="{{ route('public.cart.update') }}"
                                          class="mz-qty-form" data-qty-form>
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $item['menu_id'] }}">
                                        <div class="mz-qty">
                                            <button type="button" data-qty-step="-1" aria-label="Kurangi jumlah">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}"
                                                   min="0" max="99" aria-label="Jumlah {{ $item['menu_name'] }}">
                                            <button type="button" data-qty-step="1" aria-label="Tambah jumlah">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        <button type="submit" class="mz-qty__save" title="Perbarui jumlah">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="mz-cart-item__end">
                                    <span class="mz-cart-item__total">{{ rupiah($item['subtotal']) }}</span>
                                    <form method="POST" action="{{ route('public.cart.remove') }}">
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $item['menu_id'] }}">
                                        <button class="mz-remove" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <aside class="mz-summary">
                        <h2 class="mz-summary__title">Ringkasan</h2>
                        <div class="mz-summary__row">
                            <span>Total item</span>
                            <strong>{{ $count }} porsi</strong>
                        </div>
                        <div class="mz-summary__row">
                            <span>Subtotal</span>
                            <strong>{{ rupiah($subtotal) }}</strong>
                        </div>
                        @if($tax_enabled)
                            <div class="mz-summary__row">
                                <span>Pajak PB1 {{ (int) $tax_rate }}%</span>
                                <strong>{{ rupiah($tax_amount) }}</strong>
                            </div>
                        @endif
                        <hr class="mz-divider">
                        <div class="mz-summary__total">
                            <span>Total</span>
                            <strong>{{ rupiah($total) }}</strong>
                        </div>

                        <small class="mz-muted" style="display:block;margin-top:2px;font-size:11.5px">
                            Total sudah termasuk pajak. Pembayaran tetap dilakukan di kasir.
                        </small>

                        <form method="POST" action="{{ route('public.order.store') }}">
                            @csrf

                            <div class="mz-field mb-2">
                                <label class="mz-field__label" for="customer_name">Nama (opsional)</label>
                                <input type="text" name="customer_name" id="customer_name" class="mz-input"
                                       maxlength="50" placeholder="Nama panggilan" value="{{ old('customer_name') }}">
                            </div>

                            <div class="mz-field mb-2">
                                <label class="mz-field__label" for="table_number">Nomor meja (opsional)</label>
                                <input type="text" name="table_number" id="table_number" class="mz-input"
                                       maxlength="20" placeholder="Contoh: 5" value="{{ old('table_number') }}">
                            </div>

                            <div class="mz-field mb-3">
                                <label class="mz-field__label" for="notes">Catatan (opsional)</label>
                                <textarea name="notes" id="notes" class="mz-input mz-textarea" rows="2" maxlength="255"
                                          placeholder="Contoh: gula dikurangi, tanpa es">{{ old('notes') }}</textarea>
                            </div>

                            <button class="mz-btn mz-btn--primary mz-btn--block" type="submit">
                                <i class="bi bi-send"></i> Kirim Pesanan ke Kasir
                            </button>

                            <div class="mz-note">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>Pembayaran dilakukan di kasir. Tunjukkan kode pesanan kepada kasir setelah pesanan dikirim.</div>
                            </div>
                        </form>
                    </aside>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-qty-form]').forEach(function (form) {
        const input = form.querySelector('input[name="quantity"]');

        form.querySelectorAll('[data-qty-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                const step = parseInt(button.dataset.qtyStep, 10);
                const next = Math.min(99, Math.max(0, (parseInt(input.value, 10) || 0) + step));
                input.value = next;
            });
        });
    });
</script>
@endpush
