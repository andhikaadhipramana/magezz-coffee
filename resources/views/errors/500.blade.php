@include('errors.minimal', [
    'code' => 500,
    'title' => 'Ada gangguan di dapur kami',
    'message' => 'Sistem sedang mengalami kendala saat memproses permintaan Anda. Data transaksi Anda tidak hilang.',
    'hint' => 'Coba muat ulang halaman beberapa saat lagi. Jika masih gagal, hubungi Manager.',
    'icon' => 'bi-cup-hot',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Muat Ulang Beranda',
])
