@include('errors.minimal', [
    'code' => 419,
    'title' => 'Sesi Anda sudah berakhir',
    'message' => 'Demi keamanan, sesi login kedaluwarsa setelah didiamkan beberapa saat. Silakan masuk kembali.',
    'icon' => 'bi-hourglass-split',
    'primaryUrl' => route('login'),
    'primaryLabel' => 'Masuk Kembali',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Lihat Menu',
])
