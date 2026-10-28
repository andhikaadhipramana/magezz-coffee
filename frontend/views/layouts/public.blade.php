<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="@yield('description', 'Katalog menu Magezz Coffee — kopi, minuman, dan makanan. Pesan online, bayar di kasir.')">
    <title>@yield('title', 'Menu') &middot; @yield('brand', 'Magezz Coffee')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2b1d14">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="@yield('brand', 'Magezz Coffee')">
    <meta property="og:title" content="@yield('title', 'Menu') &middot; @yield('brand', 'Magezz Coffee')">
    <meta property="og:description" content="@yield('description', 'Katalog menu Magezz Coffee — kopi, minuman, dan makanan. Pesan online, bayar di kasir.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-cover.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Menu') &middot; @yield('brand', 'Magezz Coffee')">
    <meta name="twitter:description" content="@yield('description', 'Katalog menu Magezz Coffee — kopi, minuman, dan makanan. Pesan online, bayar di kasir.')">
    <meta name="twitter:image" content="{{ asset('images/og-cover.png') }}">

    @stack('meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,600&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset_v('css/magezz.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="mz-root mz-public">
    <nav class="mz-nav">
        <div class="mz-container mz-nav__inner">
            <a href="{{ route('public.menu') }}" class="mz-brand">
                <span class="mz-brand__mark"><i class="bi bi-cup-hot-fill"></i></span>
                <span>
                    <span class="mz-brand__name">@yield('brand', 'Magezz Coffee')</span>
                    <span class="mz-brand__sub">Menu &amp; Katalog</span>
                </span>
            </a>

            <div class="mz-nav__links">
                <a href="{{ route('public.menu') }}" class="mz-nav__link {{ request()->routeIs('public.menu') ? 'is-active' : '' }}">
                    <i class="bi bi-cup-hot"></i> Menu
                </a>
                <a href="{{ route('public.order.check') }}" class="mz-nav__link {{ request()->routeIs('public.order.check') ? 'is-active' : '' }}">
                    <i class="bi bi-search"></i> Cek Pesanan
                </a>
            </div>

            <div class="mz-nav__actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="mz-btn mz-btn--outline mz-btn--sm">
                        <i class="bi bi-speedometer2"></i> <span class="d-none d-sm-inline">Dasbor Staf</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="mz-btn mz-btn--outline mz-btn--sm">
                        <i class="bi bi-box-arrow-in-right"></i> <span class="d-none d-sm-inline">Login Staf</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    @php($lastOrder = session('public_last_order'))
    @if($lastOrder && ! request()->routeIs('public.order.show'))
        <div class="mz-lastbar">
            <div class="mz-container d-flex justify-content-center align-items-center flex-wrap gap-2">
                <i class="bi bi-receipt"></i>
                <span>Pesanan terakhir Anda: <strong>{{ $lastOrder }}</strong></span>
                <a href="{{ route('public.order.show', $lastOrder) }}" class="mz-btn mz-btn--caramel mz-btn--sm">Lihat Status</a>
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @php($showFab = ! request()->routeIs('public.cart.index', 'public.order.show', 'public.order.check'))

    @if($showFab)
        <a href="{{ route('public.cart.index') }}" id="cartButton" class="mz-fab" aria-label="Lihat keranjang pesanan">
            <i class="bi bi-basket2-fill"></i>
            <span>Keranjang</span>
            @if($publicCartCount > 0)
                <span class="cart-bubble" id="cartBubble">{{ $publicCartCount }}</span>
            @endif
        </a>
    @endif

    <footer class="mz-footer {{ $showFab ? 'mz-footer--fab' : '' }}">
        <div class="mz-container mz-footer__grid">
            <div>
                <strong>@yield('brand', 'Magezz Coffee')</strong>
                <small>Kopi, roti, dan waktu yang pelan</small>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap justify-content-center">
                <a href="{{ route('public.order.check') }}"><i class="bi bi-search me-1"></i>Cek Status Pesanan</a>
                <span>&copy; {{ date('Y') }} &middot; Harga dapat berubah sewaktu-waktu.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            let toastTimer = null;

            function showToast(message, type) {
                document.querySelectorAll('.toast-cart').forEach(function (el) { el.remove(); });

                const toast = document.createElement('div');
                toast.className = 'toast-cart' + (type === 'danger' ? ' is-danger' : '');
                toast.innerHTML = '<i class="bi ' + (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill') + '"></i><span></span>';
                toast.querySelector('span').textContent = message;
                document.body.appendChild(toast);

                requestAnimationFrame(function () { toast.classList.add('show'); });

                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    toast.classList.remove('show');
                    setTimeout(function () { toast.remove(); }, 300);
                }, 2400);
            }

            function updateBubble(count) {
                const button = document.getElementById('cartButton');
                if (!button) return;

                let bubble = document.getElementById('cartBubble');

                if (count <= 0) {
                    if (bubble) bubble.remove();
                    return;
                }

                if (!bubble) {
                    bubble = document.createElement('span');
                    bubble.className = 'cart-bubble';
                    bubble.id = 'cartBubble';
                    button.appendChild(bubble);
                }

                bubble.textContent = count;
                bubble.classList.remove('pulse');
                void bubble.offsetWidth;
                bubble.classList.add('pulse');
            }

            document.addEventListener('submit', function (event) {
                const form = event.target.closest('form[data-add-to-cart]');
                if (!form) return;

                event.preventDefault();

                const token = document.querySelector('meta[name="csrf-token"]');
                const button = form.querySelector('button');
                const label = button ? button.innerHTML : '';

                if (button) button.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.content : '',
                    },
                    body: new FormData(form),
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            showToast(data.message || 'Gagal menambahkan menu.', 'danger');
                            return;
                        }

                        updateBubble(data.count);
                        showToast(data.message, 'success');

                        if (button) {
                            button.innerHTML = '<i class="bi bi-check2 me-1"></i> Ditambahkan';
                            setTimeout(function () { button.innerHTML = label; }, 1200);
                        }
                    })
                    .catch(function () { showToast('Terjadi kesalahan, coba lagi.', 'danger'); })
                    .finally(function () { if (button) button.disabled = false; });
            });
        })();
    </script>
    <script>
        (function () {
            function markLoaded(img) {
                var host = img.closest('.has-image');
                if (host) host.classList.add('is-loaded');
            }

            document.querySelectorAll('img[data-mz-img]').forEach(function (img) {
                if (img.complete && img.naturalWidth > 0) {
                    markLoaded(img);
                    return;
                }

                img.addEventListener('load', function () { markLoaded(img); }, { once: true });
                img.addEventListener('error', function () { markLoaded(img); }, { once: true });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
