<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->code }}</title>
    <style>
        body {
            font-family: "Courier New", monospace; font-size: 12px; color: #000;
            max-width: 300px; margin: 0 auto; padding: 12px;
        }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .store-name { font-size: 15px; font-weight: bold; letter-spacing: .5px; }
        .no-print { text-align: center; margin-bottom: 12px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button onclick="window.print()">Cetak Ulang</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="center">
        <div class="store-name">MAGEZZ COFFEE</div>
        <div>{{ \App\Models\Setting::get('outlet_address', '') }}</div>
        <div>Telp: {{ \App\Models\Setting::get('outlet_phone', '-') }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr><td>No.</td><td class="right">{{ $transaction->code }}</td></tr>
        <tr><td>Tanggal</td><td class="right">{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="right">{{ $transaction->user?->name }}</td></tr>
    </table>

    <div class="divider"></div>

    @foreach($transaction->items as $item)
        <div class="bold">{{ $item->menu_name }}</div>
        @if($item->variant_info)
            <div style="font-size:11px">{{ $item->variant_info }}</div>
        @endif
        <table>
            <tr>
                <td>{{ $item->quantity }} x {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                <td class="right">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endforeach

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if((float) $transaction->discount_amount > 0)
            <tr>
                <td>Diskon</td>
                <td class="right">-{{ number_format((float) $transaction->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if((float) $transaction->tax_amount > 0)
            <tr>
                <td>Pajak PB1</td>
                <td class="right">{{ number_format((float) $transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="bold">
            <td style="font-size:13px">TOTAL</td>
            <td class="right" style="font-size:13px">Rp {{ number_format((float) $transaction->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>{{ $transaction->paymentLabel() }}</td>
            <td class="right">{{ number_format((float) $transaction->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="right">{{ number_format((float) $transaction->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="center" style="font-size:11px">
        {{ \App\Models\Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda') }}
    </div>

    @if($transaction->status === 'void')
        <div class="divider"></div>
        <div class="center bold" style="font-size:14px">*** VOID ***</div>
    @endif
</body>
</html>
