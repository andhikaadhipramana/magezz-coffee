@extends('layouts.public')

@section('title', 'Menu')
@section('brand', $outletName)

@section('content')
    <section class="mz-hero mz-grain">
        <div class="mz-container mz-hero__grid {{ $signatureMenu ? '' : 'mz-hero__grid--single' }}">
            <div>
                <div class="mz-eyebrow">Katalog Menu &middot; {{ $outletName }}</div>
                <h1 class="mz-hero__title">Ah cuma dunia,<br><em>kopi satu</em>, Mas.</h1>
                <p class="mz-hero__lead">
                    Pilih menu favoritmu, kirim pesanan, lalu bayar di kasir.
                    Tanpa aplikasi tambahan, tanpa ribet.
                </p>

                <form method="GET" action="{{ route('public.menu') }}" class="mz-searchbar">
                    @if($activeCategoryId)
                        <input type="hidden" name="category_id" value="{{ $activeCategoryId }}">
                    @endif
                    <span class="mz-searchbar__icon"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari kopi, teh, atau makanan...">
                    <button type="submit" class="mz-btn mz-btn--primary">
                        <i class="bi bi-search d-sm-none"></i>
                        <span class="d-none d-sm-inline">Cari Menu</span>
                    </button>
                </form>

                <div class="mz-hero__meta">
                    <span><i class="bi bi-check2-circle"></i> {{ $categories->count() }} kategori</span>
                    <span><i class="bi bi-check2-circle"></i> Bayar di kasir</span>
                    <span><i class="bi bi-check2-circle"></i> Tanpa aplikasi</span>
                </div>
            </div>

            @if($signatureMenu)
                <div class="mz-hero__art mz-hero__art--photo">
                    <form method="POST" action="{{ route('public.cart.add') }}" class="mz-signature-form">
                        @csrf
                        <input type="hidden" name="menu_id" value="{{ $signatureMenu->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect" value="cart">

                        <button type="submit"
                                class="mz-signature-card"
                                aria-label="Tambah {{ $signatureMenu->name }} ke keranjang"
                                title="Tambah {{ $signatureMenu->name }} ke keranjang">
                            @if($signatureMenu->image_url)
                                <img class="mz-signature-card__image"
                                     src="{{ $signatureMenu->image_url }}"
                                     @if($signatureMenu->image_srcset)
                                         srcset="{{ $signatureMenu->image_srcset }}"
                                         sizes="(max-width: 575px) 100vw, 420px"
                                     @endif
                                     alt="Foto {{ $signatureMenu->name }}"
                                     loading="eager"
                                     fetchpriority="high"
                                     decoding="async"
                                     data-mz-img
                                     data-signature-photo>
                            @else
                                <span class="mz-signature-card__fallback" aria-hidden="true">
                                    <i class="bi bi-cup-hot-fill"></i>
                                </span>
                            @endif

                            <span class="mz-signature-card__caption">
                                <span class="mz-signature-card__eyebrow">
                                    <i class="bi bi-star-fill"></i> Signature
                                </span>
                                <strong class="mz-signature-card__name">{{ $signatureMenu->name }}</strong>
                                <span class="mz-signature-card__price">{{ rupiah($signatureMenu->price) }}</span>
                            </span>

                            <span class="mz-signature-card__cta" aria-hidden="true">
                                <i class="bi bi-plus-lg"></i>
                            </span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <div class="mz-page mz-page--menu">
        <div class="mz-container">
            <div class="mz-toolbar">
                @if($categories->isNotEmpty())
                    <div class="mz-chips">
                        <a href="{{ route('public.menu', array_filter(['q' => $search ?: null])) }}"
                           class="mz-chip {{ $activeCategoryId ? '' : 'is-active' }}">Semua</a>
                        @foreach($categories as $category)
                            <a href="{{ route('public.menu', array_filter(['q' => $search ?: null, 'category_id' => $category->id])) }}"
                               class="mz-chip {{ $activeCategoryId === $category->id ? 'is-active' : '' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($search !== '' || $activeCategoryId)
                    <span class="mz-muted" style="font-size:12.5px">
                        Menampilkan {{ $totalMenus }} menu
                        @if($search !== '')
                            untuk &ldquo;<strong style="color:var(--mz-ink-900)">{{ $search }}</strong>&rdquo;
                        @endif
                    </span>
                @endif
            </div>

            @forelse($menusByCategory as $categoryId => $menus)
                @php
                    $category = $categories->firstWhere('id', $categoryId);
                    $categoryName = \Illuminate\Support\Str::lower($category?->name ?? '');
                    $icon = match (true) {
                        str_contains($categoryName, 'non-kopi') => 'bi-cup-straw',
                        str_contains($categoryName, 'kopi') => 'bi-cup-hot-fill',
                        str_contains($categoryName, 'teh') => 'bi-cup-straw',
                        str_contains($categoryName, 'makan'), str_contains($categoryName, 'roti'),
                        str_contains($categoryName, 'snack'), str_contains($categoryName, 'pastry') => 'bi-egg-fried',
                        str_contains($categoryName, 'dessert'), str_contains($categoryName, 'kue') => 'bi-cake2',
                        default => 'bi-cup',
                    };
                    $tile = match (true) {
                        str_contains($categoryName, 'non-kopi') => 'mz-menu-card__tile--teh',
                        str_contains($categoryName, 'kopi') => 'mz-menu-card__tile--kopi',
                        str_contains($categoryName, 'teh') => 'mz-menu-card__tile--teh',
                        str_contains($categoryName, 'makan'), str_contains($categoryName, 'roti'),
                        str_contains($categoryName, 'snack'), str_contains($categoryName, 'pastry') => 'mz-menu-card__tile--makanan',
                        str_contains($categoryName, 'dessert'), str_contains($categoryName, 'kue') => 'mz-menu-card__tile--dessert',
                        default => 'mz-menu-card__tile--susu',
                    };
                @endphp

                <div class="mz-section-head">
                    <h2><i class="bi {{ $icon }} me-2" style="color:var(--mz-caramel-600);font-size:.8em"></i>{{ $category?->name }}</h2>
                    <span>{{ $menus->count() }} menu</span>
                </div>

                <div class="mz-menu-grid">
                    @foreach($menus as $menu)
                        <article class="mz-menu-card">
                            <div class="mz-menu-card__tile {{ $tile }} {{ $menu->image_url ? 'has-image' : '' }}">
                                @if($menu->is_signature)
                                    <span class="mz-flag"><i class="bi bi-star-fill"></i> Signature</span>
                                @endif
                                @if($menu->image_url)
                                    <img class="mz-menu-card__tile-img" src="{{ $menu->image_url }}"
                                         @if($menu->image_srcset)
                                             srcset="{{ $menu->image_srcset }}"
                                             sizes="(max-width: 575px) 45vw, (max-width: 991px) 30vw, 200px"
                                         @endif
                                         alt="Foto {{ $menu->name }}" loading="lazy" decoding="async" data-mz-img>
                                @else
                                    <i class="bi {{ $icon }}"></i>
                                @endif
                            </div>
                            <div class="mz-menu-card__body">
                                <h3 class="mz-menu-card__name">{{ $menu->name }}</h3>
                                <p class="mz-menu-card__desc">
                                    {{ $menu->description ?: 'Dibuat segar setiap pesanan.' }}
                                </p>
                                <div class="mz-menu-card__foot">
                                    <span class="mz-price">{{ rupiah($menu->price) }}</span>
                                    <form method="POST" action="{{ route('public.cart.add') }}" data-add-to-cart>
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="mz-add" type="submit">
                                            <i class="bi bi-plus-lg"></i> Tambah
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @empty
                <div class="mz-empty" style="margin-top:18px">
                    <i class="bi bi-emoji-frown"></i>
                    <strong>Menu tidak ditemukan</strong>
                    <p>
                        @if($search !== '')
                            Tidak ada menu yang cocok dengan &ldquo;{{ $search }}&rdquo;.
                        @else
                            Belum ada menu yang tersedia saat ini.
                        @endif
                    </p>
                    @if($search !== '' || $activeCategoryId)
                        <div style="margin-top:16px">
                            <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--outline mz-btn--sm">
                                <i class="bi bi-arrow-counterclockwise"></i> Tampilkan semua menu
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
@endsection
