@php
    $code = $code ?? 500;
    $title = $title ?? 'Terjadi kesalahan';
    $message = $message ?? 'Silakan coba lagi beberapa saat lagi.';
    $hint = $hint ?? null;
    $icon = $icon ?? 'bi-exclamation-triangle';
    $primaryUrl = $primaryUrl ?? url('/');
    $primaryLabel = $primaryLabel ?? 'Kembali ke Beranda';
    $secondaryUrl = $secondaryUrl ?? null;
    $secondaryLabel = $secondaryLabel ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $code }} &middot; {{ $title }} &middot; Magezz Coffee</title>
    <meta name="description" content="{{ $title }} — Magezz Coffee.">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#2b1d14">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;1,9..144,700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset_v('css/magezz.css') }}" rel="stylesheet">
</head>

<body class="mz-root mz-error-page">
    <main class="mz-error">
        <div class="mz-error__card">
            <a class="mz-error__brand" href="{{ url('/') }}">
                <span class="mz-brand__mark"><i class="bi bi-cup-hot-fill"></i></span>
                <span>Magezz Coffee</span>
            </a>

            <div class="mz-error__code">{{ $code }}</div>
            <h1 class="mz-error__title">{{ $title }}</h1>
            <p class="mz-error__message">{{ $message }}</p>

            @if ($hint)
                <p class="mz-error__hint"><i class="bi bi-info-circle"></i> {{ $hint }}</p>
            @endif

            <div class="mz-error__actions">
                <a href="{{ $primaryUrl }}" class="mz-btn mz-btn--primary">
                    <i class="bi bi-arrow-left"></i> {{ $primaryLabel }}
                </a>
                @if ($secondaryUrl)
                    <a href="{{ $secondaryUrl }}" class="mz-btn mz-btn--outline">{{ $secondaryLabel }}</a>
                @endif
            </div>

            <div class="mz-error__foot">
                <i class="bi {{ $icon }}"></i>
                <span>Magezz Coffee &middot; {{ date('Y') }}</span>
            </div>
        </div>
    </main>
</body>

</html>
