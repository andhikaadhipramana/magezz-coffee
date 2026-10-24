@include('errors.minimal', [
    'code' => 404,
    'title' => 'Halaman tidak ditemukan',
    'message' => 'Alamat yang Anda tuju tidak ada atau sudah dipindahkan. Mungkin tautannya salah ketik?',
    'hint' => 'Coba kembali ke katalog menu, atau cek status pesanan Anda lewat kode pesanan.',
    'icon' => 'bi-signpost-split',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Menu',
    'secondaryUrl' => route('public.order.check'),
    'secondaryLabel' => 'Cek Pesanan',
])
