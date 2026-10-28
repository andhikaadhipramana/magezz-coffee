@extends('layouts.public')

@section('title', 'Cek Status Pesanan')

@push('meta')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
    <div class="mz-page">
        <div class="mz-container" style="max-width:560px">
            <div class="mz-panel" style="padding:34px 30px;text-align:center">
                <div class="mz-empty" style="border:0;background:transparent;padding:0 0 18px">
                    <i class="bi bi-search" style="margin-bottom:12px"></i>
                    <strong style="font-family:var(--mz-font-display);font-size:26px;font-weight:600;letter-spacing:-.02em">
                        Cek Status Pesanan
                    </strong>
                    <p style="margin-top:8px">
                        Masukkan kode pesanan yang Anda terima, contoh:
                        <strong style="color:var(--mz-ink-900)">MGO-20261020-0001</strong>
                    </p>
                </div>

                @if(! empty($error))
                    <div class="mz-note" style="background:var(--mz-danger-100);color:var(--mz-danger-700);text-align:left;margin-bottom:16px">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>{{ $error }}</div>
                    </div>
                @endif

                <form method="get" action="{{ route('public.order.check') }}" class="d-flex gap-2 flex-wrap justify-content-center">
                    <input
                        type="text"
                        name="code"
                        value="{{ $code }}"
                        class="mz-input mz-code-input"
                        placeholder="MGO-YYYYMMDD-XXXX"
                        maxlength="30"
                        required
                        autofocus
                    >
                    <button type="submit" class="mz-btn mz-btn--primary">
                        <i class="bi bi-search"></i> Cari Pesanan
                    </button>
                </form>

                @if($lastCode)
                    <div style="margin-top:24px;padding-top:20px;border-top:1px dashed var(--mz-line-strong)">
                        <small class="mz-muted" style="display:block;margin-bottom:8px;font-size:12px">
                            Anda punya pesanan terakhir di perangkat ini:
                        </small>
                        <a href="{{ route('public.order.show', $lastCode) }}" class="mz-btn mz-btn--cream mz-btn--sm">
                            <i class="bi bi-receipt"></i> Buka {{ $lastCode }}
                        </a>
                    </div>
                @endif

                <div style="margin-top:22px">
                    <a href="{{ route('public.menu') }}" class="mz-btn mz-btn--ghost mz-btn--sm">
                        <i class="bi bi-cup-hot"></i> Kembali ke Katalog
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .mz-code-input {
        flex: 1;
        min-width: 200px;
        max-width: 280px;
        text-align: center;
        font-family: "Courier New", ui-monospace, monospace;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
</style>
@endpush
