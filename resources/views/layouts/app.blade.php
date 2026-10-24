<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2b1d14">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Dashboard') &middot; Magezz Coffee</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,600&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset_v('css/magezz.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body class="mz-root mz-staff {{ request()->routeIs('pos.*') ? 'mz-staff--pos' : '' }}">
    <div class="mz-drawer-backdrop" data-mz-drawer-backdrop aria-hidden="true"></div>

    <div class="mz-toasts" id="mzToasts" role="status" aria-live="polite" aria-atomic="false"></div>

    <dialog class="mz-dialog" id="mzConfirmDialog" aria-labelledby="mzConfirmTitle">
        <form method="dialog" class="mz-dialog__form">
            <div class="mz-dialog__icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <h2 class="mz-dialog__title" id="mzConfirmTitle">Konfirmasi</h2>
            <p class="mz-dialog__text" data-mz-confirm-text>Yakin melanjutkan tindakan ini?</p>
            <div class="mz-dialog__actions">
                <button type="submit" value="cancel" class="mz-btn mz-btn--outline">Batal</button>
                <button type="submit" value="confirm" class="mz-btn mz-btn--danger" data-mz-confirm-ok>
                    Ya, Lanjutkan
                </button>
            </div>
        </form>
    </dialog>

    <nav class="mz-sidebar" id="mzSidebar" aria-label="Navigasi utama">
        <button type="button" class="mz-sidebar__close" data-mz-drawer-close aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
        <a href="{{ route('dashboard') }}" class="mz-sidebar__brand text-decoration-none"
            title="Dashboard Magezz Coffee">
            <span class="mz-brand__mark"><i class="bi bi-cup-hot-fill"></i></span>
            <div>
                <strong>Magezz</strong>
                <small>Coffee Management</small>
            </div>
        </a>

        <a class="mz-navlink {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}"
            title="Dashboard">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a class="mz-navlink {{ request()->routeIs('pos.*') ? 'is-active' : '' }}" href="{{ route('pos.index') }}"
            title="Kasir (POS)">
            <i class="bi bi-basket"></i><span>Kasir (POS)</span>
        </a>
        <a class="mz-navlink {{ request()->routeIs('orders.*') ? 'is-active' : '' }}" href="{{ route('orders.index') }}"
            title="Pesanan Online">
            <i class="bi bi-bell"></i><span>Pesanan</span>
            @if ($onlineOrdersPending > 0)
                <span class="mz-badge">{{ $onlineOrdersPending }}</span>
            @endif
        </a>
        <a class="mz-navlink {{ request()->routeIs('transactions.*') ? 'is-active' : '' }}"
            href="{{ route('transactions.index') }}" title="Transaksi">
            <i class="bi bi-receipt"></i><span>Transaksi</span>
        </a>

        @if (auth()->user()->isManager())
            <div class="mz-sidebar__section">Manajemen</div>
            <a class="mz-navlink {{ request()->routeIs('categories.*') ? 'is-active' : '' }}"
                href="{{ route('categories.index') }}" title="Kategori">
                <i class="bi bi-tags"></i><span>Kategori</span>
            </a>
            <a class="mz-navlink {{ request()->routeIs('menus.*') ? 'is-active' : '' }}"
                href="{{ route('menus.index') }}" title="Menu">
                <i class="bi bi-cup-straw"></i><span>Menu</span>
            </a>
            <a class="mz-navlink {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"
                href="{{ route('reports.index') }}" title="Laporan Penjualan">
                <i class="bi bi-graph-up-arrow"></i><span>Laporan</span>
            </a>
        @endif

        <div class="mz-sidebar__section">Operasional</div>
        <a class="mz-navlink {{ request()->routeIs('shifts.*') ? 'is-active' : '' }}"
            href="{{ route('shifts.index') }}" title="Shift Kasir">
            <i class="bi bi-clock-history"></i><span>Shift Kasir</span>
        </a>
        <a class="mz-navlink {{ request()->routeIs('attendances.*') ? 'is-active' : '' }}"
            href="{{ route('attendances.index') }}" title="Absensi">
            <i class="bi bi-calendar-check"></i><span>Absensi</span>
        </a>

        @if (auth()->user()->isManager())
            <div class="mz-sidebar__section">Administrasi</div>
            <a class="mz-navlink {{ request()->routeIs('users.*') ? 'is-active' : '' }}"
                href="{{ route('users.index') }}" title="Pengguna">
                <i class="bi bi-people"></i><span>Pengguna</span>
            </a>
            <a class="mz-navlink {{ request()->routeIs('settings.*') ? 'is-active' : '' }}"
                href="{{ route('settings.index') }}" title="Pengaturan">
                <i class="bi bi-gear"></i><span>Pengaturan</span>
            </a>
        @endif

        <div class="mz-sidebar__user">
            <span
                class="mz-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 2)) }}</span>
            <div>
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->roleLabel() }}</small>
            </div>
        </div>
    </nav>

    <div class="mz-staff-content">
        <header class="mz-staff-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="mz-topbar-btn d-lg-none" data-mz-drawer-toggle
                    aria-controls="mzSidebar" aria-expanded="false" aria-label="Buka menu navigasi">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="mz-staff-topbar__title">@yield('title', 'Dashboard')</div>
                    <span class="mz-staff-topbar__sub">@yield('subtitle', now()->translatedFormat('l, d F Y'))</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <span class="text-muted d-none d-lg-inline" style="font-size:11.5px">
                    <i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('d M Y') }}
                </span>
                <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--outline mz-btn--sm d-none d-md-inline-flex">
                    <i class="bi bi-shop"></i> Lihat Katalog
                </a>

                <div class="dropdown">
                    <button class="mz-userbtn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span
                            class="mz-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 2)) }}</span>
                        <span class="text-start lh-1 d-none d-sm-block">
                            <span class="mz-userbtn__name">{{ auth()->user()->name }}</span>
                            <span class="mz-userbtn__role">{{ auth()->user()->roleLabel() }}</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted">{{ auth()->user()->email }}</span></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="mz-staff-main {{ request()->routeIs('pos.index') ? 'mz-pos-active' : '' }}">
            @if (session('success'))
                <div class="d-none" data-mz-flash="success" data-message="{{ session('success') }}"></div>
            @endif
            @if (session('error'))
                <div class="d-none" data-mz-flash="danger" data-message="{{ session('error') }}"></div>
            @endif
            @if ($errors->any())
                <div class="mz-alert mz-alert--danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Periksa kembali input Anda:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="mz-staff-footer">
            &copy; {{ date('Y') }} Magezz Coffee &middot; Sistem Manajemen Coffee Shop
        </footer>
    </div>

    @unless (request()->routeIs('pos.*'))
        <nav class="mz-bottomnav" aria-label="Navigasi cepat">
            <a href="{{ route('dashboard') }}"
                class="mz-bottomnav__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <i class="bi bi-speedometer2"></i><span>Dasbor</span>
            </a>
            <a href="{{ route('pos.index') }}"
                class="mz-bottomnav__item {{ request()->routeIs('pos.*') ? 'is-active' : '' }}">
                <i class="bi bi-basket"></i><span>Kasir</span>
            </a>
            <a href="{{ route('orders.index') }}"
                class="mz-bottomnav__item {{ request()->routeIs('orders.*') ? 'is-active' : '' }}">
                <i class="bi bi-bell"></i><span>Pesanan</span>
                @if ($onlineOrdersPending > 0)
                    <span class="mz-badge">{{ $onlineOrdersPending }}</span>
                @endif
            </a>
            <a href="{{ route('shifts.index') }}"
                class="mz-bottomnav__item {{ request()->routeIs('shifts.*') ? 'is-active' : '' }}">
                <i class="bi bi-clock-history"></i><span>Shift</span>
            </a>
            <button type="button" class="mz-bottomnav__item" data-mz-drawer-toggle
                aria-controls="mzSidebar" aria-expanded="false" aria-label="Buka menu lainnya">
                <i class="bi bi-grid"></i><span>Lainnya</span>
            </button>
        </nav>
    @endunless

    <script>
        (function () {
            var body = document.body;
            var sidebar = document.getElementById('mzSidebar');
            if (!sidebar) return;

            var backdrop = document.querySelector('[data-mz-drawer-backdrop]');
            var openers = document.querySelectorAll('[data-mz-drawer-toggle]');
            var closers = document.querySelectorAll('[data-mz-drawer-close]');
            var lastFocused = null;

            function isMobile() {
                return window.matchMedia('(max-width: 991.98px)').matches;
            }

            function isOpen() {
                return body.classList.contains('mz-drawer-open');
            }

            function sync() {
                if (isMobile() && !isOpen()) {
                    sidebar.setAttribute('aria-hidden', 'true');
                    sidebar.inert = true;
                } else {
                    sidebar.removeAttribute('aria-hidden');
                    sidebar.inert = false;
                }
            }

            function openDrawer() {
                lastFocused = document.activeElement;
                body.classList.add('mz-drawer-open');
                if (backdrop) backdrop.classList.add('is-open');
                openers.forEach(function (el) { el.setAttribute('aria-expanded', 'true'); });
                sync();

                var first = sidebar.querySelector('a, button');
                if (first) first.focus({ preventScroll: true });
            }

            function closeDrawer() {
                body.classList.remove('mz-drawer-open');
                if (backdrop) backdrop.classList.remove('is-open');
                openers.forEach(function (el) { el.setAttribute('aria-expanded', 'false'); });
                sync();

                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus({ preventScroll: true });
                }
            }

            openers.forEach(function (el) {
                el.addEventListener('click', function () {
                    isOpen() ? closeDrawer() : openDrawer();
                });
            });

            closers.forEach(function (el) { el.addEventListener('click', closeDrawer); });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && isOpen()) closeDrawer();
            });

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile() && isOpen()) closeDrawer();
                });
            });

            window.addEventListener('resize', function () {
                if (!isMobile() && isOpen()) closeDrawer();
                sync();
            });

            sync();
        })();
    </script>

    <script>
        (function () {
            /* ---------- Toast ---------- */
            var host = document.getElementById('mzToasts');
            var MAX_TOASTS = 3;

            function dismiss(el) {
                if (!el || !el.isConnected) return;
                el.classList.remove('is-visible');
                setTimeout(function () { el.remove(); }, 280);
            }

            function mzToast(message, type) {
                if (!host || !message) return;

                var danger = type === 'danger' || type === 'error';
                var el = document.createElement('div');
                el.className = 'mz-toast ' + (danger ? 'mz-toast--danger' : 'mz-toast--success');
                el.setAttribute('role', danger ? 'alert' : 'status');

                var icon = document.createElement('i');
                icon.className = 'bi ' + (danger ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');

                var text = document.createElement('span');
                text.className = 'mz-toast__text';
                text.textContent = message;

                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'mz-toast__close';
                close.setAttribute('aria-label', 'Tutup notifikasi');
                close.innerHTML = '<i class="bi bi-x-lg"></i>';

                el.appendChild(icon);
                el.appendChild(text);
                el.appendChild(close);
                host.appendChild(el);

                while (host.children.length > MAX_TOASTS) {
                    host.firstElementChild.remove();
                }

                requestAnimationFrame(function () { el.classList.add('is-visible'); });

                var timer = setTimeout(function () { dismiss(el); }, danger ? 6000 : 4000);

                function pause() {
                    clearTimeout(timer);
                }

                function resume() {
                    clearTimeout(timer);
                    timer = setTimeout(function () { dismiss(el); }, 2000);
                }

                close.addEventListener('click', function () {
                    clearTimeout(timer);
                    dismiss(el);
                });
                el.addEventListener('mouseenter', pause);
                el.addEventListener('mouseleave', resume);
                el.addEventListener('focusin', pause);
                el.addEventListener('focusout', resume);
            }

            window.mzToast = mzToast;

            document.querySelectorAll('[data-mz-flash]').forEach(function (node) {
                mzToast(node.getAttribute('data-message'), node.getAttribute('data-mz-flash'));
            });

            /* ---------- Konfirmasi ---------- */
            var dialog = document.getElementById('mzConfirmDialog');
            var pendingForm = null;

            function askConfirm(form) {
                var message = form.getAttribute('data-mz-confirm') || 'Yakin melanjutkan tindakan ini?';

                if (!dialog || typeof dialog.showModal !== 'function') {
                    if (window.confirm(message)) {
                        form.dataset.mzConfirmed = '1';
                        form.submit();
                    }
                    return;
                }

                pendingForm = form;
                dialog.querySelectorAll('button').forEach(function (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-loading');
                    btn.removeAttribute('aria-busy');
                });
                dialog.querySelector('[data-mz-confirm-text]').textContent = message;
                dialog.returnValue = '';
                dialog.showModal();
            }

            if (dialog) {
                dialog.addEventListener('close', function () {
                    var form = pendingForm;
                    pendingForm = null;

                    if (!form || dialog.returnValue !== 'confirm') return;

                    form.dataset.mzConfirmed = '1';

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            }

            /* ---------- Submit: konfirmasi + anti dobel ---------- */
            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form || form.tagName !== 'FORM') return;

                var message = form.getAttribute('data-mz-confirm');
                if (message && form.dataset.mzConfirmed !== '1') {
                    event.preventDefault();
                    askConfirm(form);
                    return;
                }

                var method = (form.getAttribute('method') || 'get').toLowerCase();
                if (method === 'get' || method === 'dialog') return;

                if (form.dataset.mzSubmitting === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.mzSubmitting = '1';

                form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.classList.add('is-loading');
                    btn.setAttribute('aria-busy', 'true');
                });
            });

            /* Pulihkan tombol bila halaman diambil dari riwayat (bfcache) */
            window.addEventListener('pageshow', function () {
                document.querySelectorAll('form[data-mz-submitting]').forEach(function (form) {
                    delete form.dataset.mzSubmitting;
                    delete form.dataset.mzConfirmed;
                });

                document.querySelectorAll('.is-loading[aria-busy]').forEach(function (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-loading');
                    btn.removeAttribute('aria-busy');
                });
            });

            /* ---------- Tabel staf menjadi kartu di layar kecil ---------- */
            document.querySelectorAll('table.mz-table-cards').forEach(function (table) {
                var headerRow = table.querySelector('thead tr');
                if (!headerRow) return;

                var labels = Array.prototype.map.call(headerRow.children, function (th) {
                    return (th.textContent || '').trim();
                });

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var cells = row.children;

                    if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                        row.classList.add('mz-table-cards__empty');
                        return;
                    }

                    Array.prototype.forEach.call(cells, function (cell, index) {
                        if (!cell.hasAttribute('data-label') && labels[index]) {
                            cell.setAttribute('data-label', labels[index]);
                        }
                    });

                    var lastCell = cells[cells.length - 1];
                    if (lastCell && lastCell.querySelector('a, button, form')) {
                        lastCell.classList.add('mz-td-actions');
                    }
                });
            });

            /* Skeleton foto: tandai host setelah gambar selesai dimuat */
            document.querySelectorAll('img[data-mz-img]').forEach(function (img) {
                function markLoaded() {
                    var host = img.closest('.has-image');
                    if (host) host.classList.add('is-loaded');
                }

                if (img.complete && img.naturalWidth > 0) {
                    markLoaded();
                } else {
                    img.addEventListener('load', markLoaded, { once: true });
                    img.addEventListener('error', markLoaded, { once: true });
                }
            });
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>

</html>
