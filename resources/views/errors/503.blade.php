@include('errors.minimal', [
    'code' => 503,
    'title' => 'Sedang tutup sebentar',
    'message' => 'Magezz Coffee sedang melakukan pemeliharaan singkat. Silakan kembali beberapa saat lagi.',
    'icon' => 'bi-cone-striped',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Coba Lagi',
])
