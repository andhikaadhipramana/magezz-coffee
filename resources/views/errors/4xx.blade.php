@php($status = isset($exception) ? $exception->getStatusCode() : 400)

@include('errors.minimal', [
    'code' => $status,
    'title' => 'Permintaan tidak dapat diproses',
    'message' => 'Permintaan Anda tidak valid atau sudah tidak berlaku. Silakan ulangi dari awal.',
    'icon' => 'bi-exclamation-circle',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Menu',
    'secondaryUrl' => route('public.order.check'),
    'secondaryLabel' => 'Cek Pesanan',
])
