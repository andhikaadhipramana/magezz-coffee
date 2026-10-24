<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Masuk &middot; Magezz Coffee</title>
    <meta name="theme-color" content="#2b1d14">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,600&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset_v('css/magezz.css') }}" rel="stylesheet">
</head>
<body class="mz-root">
    <div class="mz-login">
        <section class="mz-login__art mz-grain">
            <div class="mz-login__brand">
                <span class="mz-brand__mark"><i class="bi bi-cup-hot-fill"></i></span>
                <div>
                    <strong>Magezz Coffee</strong>
                    <small>Coffee Management</small>
                </div>
            </div>

            <div class="mz-login__quote">
                <span><i class="bi bi-star-fill"></i> Sejak pagi, selalu segar</span>
                <h1>Ah cuma dunia,<br>kopi satu, <em style="font-style:italic;color:#e6c9a3">Mas.</em></h1>
                <p>
                    Kelola pesanan, transaksi, dan laporan dalam satu tempat.
                    Dibuat untuk tim yang bekerja tenang di balik bar.
                </p>
            </div>

            <div class="mz-login__meta">
                <span><i class="bi bi-check2-circle me-1"></i> Katalog publik</span>
                <span><i class="bi bi-check2-circle me-1"></i> Kasir &amp; shift</span>
                <span><i class="bi bi-check2-circle me-1"></i> Laporan harian</span>
            </div>
        </section>

        <section class="mz-login__form-wrap">
            <div class="mz-login__form">
                <div class="mz-login__mobile-brand d-flex d-lg-none">
                    <span class="mz-brand__mark"><i class="bi bi-cup-hot-fill"></i></span>
                    <div>
                        <strong>Magezz Coffee</strong>
                        <small>Coffee Management</small>
                    </div>
                </div>

                <div class="mz-eyebrow mb-2">Area Staf</div>
                <h2>Masuk ke Magezz</h2>
                <p>Gunakan akun kasir atau manager yang terdaftar.</p>

                @if(session('success'))
                    <div class="mz-note" style="margin-bottom:16px">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mz-note" style="background:var(--mz-danger-100);color:var(--mz-danger-700);margin-bottom:16px">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mz-field mb-3">
                        <label class="mz-field__label" for="email">Email</label>
                        <div class="mz-input-icon">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" id="email" class="mz-input" value="{{ old('email') }}"
                                   placeholder="nama@magezz.test" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div class="mz-field mb-3">
                        <label class="mz-field__label" for="password">Kata Sandi</label>
                        <div class="mz-input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" id="password" class="mz-input"
                                   placeholder="••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember" style="font-size:12.5px">Ingat saya</label>
                        </div>
                        <a href="{{ route('public.order.check') }}" style="font-size:12px;color:var(--mz-ink-500);text-decoration:none">
                            Cek pesanan?
                        </a>
                    </div>

                    <button type="submit" class="mz-btn mz-btn--primary mz-btn--block">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('public.menu') }}" style="font-size:12.5px;color:var(--mz-ink-500);text-decoration:none">
                        <i class="bi bi-cup-hot me-1"></i> Lihat Menu (Katalog)
                    </a>
                </div>

                <div class="mz-login__demo">
                    <strong><i class="bi bi-info-circle me-1"></i> Akun Demo</strong>
                    Manager: <code>andhika@magezz.test</code><br>
                    Kasir: <code>alexander@magezz.test</code>, <code>veronica@magezz.test</code><br>
                    Kata sandi: <code>password</code>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
