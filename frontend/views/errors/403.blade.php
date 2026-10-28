@include('errors.minimal', [
    'code' => 403,
    'title' => 'Akses ditolak',
    'message' => 'Akun Anda tidak memiliki izin untuk membuka halaman ini, atau akun sedang tidak aktif.',
    'hint' => 'Jika akun Anda baru dinonaktifkan, hubungi Manager untuk mengaktifkan kembali.',
    'icon' => 'bi-shield-lock',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Menu',
    'secondaryUrl' => route('public.order.check'),
    'secondaryLabel' => 'Cek Pesanan',
])
