@php($status = isset($exception) ? $exception->getStatusCode() : 500)

@include('errors.minimal', [
    'code' => $status,
    'title' => 'Ada gangguan di dapur kami',
    'message' => 'Sistem sedang mengalami kendala saat memproses permintaan Anda. Coba beberapa saat lagi.',
    'icon' => 'bi-cup-hot',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Muat Ulang Beranda',
])
