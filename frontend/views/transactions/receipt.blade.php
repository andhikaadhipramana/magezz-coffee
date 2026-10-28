<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->code }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px 8px 16px;
            background: #fff;
            color: #111;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 14px;
        }

        .toolbar button {
            padding: 8px 16px;
            border: 1px solid #cbbba7;
            border-radius: 999px;
            background: #fff;
            color: #2b1d14;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .toolbar button:hover {
            background: #f5ede2;
        }

        .brand {
            text-align: center;
        }

        .brand__name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .brand__line {
            font-size: 10.5px;
            color: #444;
        }

        .rule {
            margin: 8px 0;
            border-top: 1px dashed #8a8a8a;
        }

        .meta,
        .sum {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td,
        .sum td {
            padding: 1px 0;
            vertical-align: top;
        }

        .meta td:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .sum td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .meta td:first-child {
            color: #444;
        }

        .item {
            margin-bottom: 7px;
        }

        .item:last-child {
            margin-bottom: 0;
        }

        .item__name {
            font-weight: 700;
        }

        .item__variant {
            font-size: 10px;
            color: #555;
        }

        .item__row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .item__qty {
            color: #333;
        }

        .sum .total td {
            padding-top: 6px;
            font-size: 13px;
            font-weight: 800;
        }

        .footer {
            text-align: center;
            font-size: 10.5px;
            color: #333;
        }

        .void {
            margin-top: 10px;
            padding: 4px;
            border: 2px solid #000;
            text-align: center;
            font-weight: 800;
            letter-spacing: 0.24em;
        }

        @media print {
            .toolbar {
                display: none;
            }

            body {
                max-width: none;
                padding: 0;
            }

            .brand__line,
            .item__variant,
            .footer,
            .meta td:first-child {
                color: #000;
            }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $outletName = \App\Models\Setting::get('outlet_name', 'Magezz Coffee');
        $outletAddress = \App\Models\Setting::get('outlet_address');
        $outletPhone = \App\Models\Setting::get('outlet_phone');
    @endphp

    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <div class="brand">
        <div class="brand__name">{{ $outletName }}</div>
        @if($outletAddress)
            <div class="brand__line">{{ $outletAddress }}</div>
        @endif
        @if($outletPhone)
            <div class="brand__line">Telp {{ $outletPhone }}</div>
        @endif
    </div>

    <div class="rule"></div>

    <table class="meta">
        <tr><td>No. Struk</td><td>{{ $transaction->code }}</td></tr>
        <tr><td>Waktu</td><td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td>{{ $transaction->user?->name }}</td></tr>
        <tr><td>Pembayaran</td><td>{{ $transaction->paymentLabel() }}</td></tr>
    </table>

    <div class="rule"></div>

    @foreach($transaction->items as $item)
        <div class="item">
            <div class="item__name">{{ $item->menu_name }}</div>
            @if($item->variant_info)
                <div class="item__variant">{{ $item->variant_info }}</div>
            @endif
            <div class="item__row">
                <span class="item__qty">{{ $item->quantity }} &times; {{ rupiah($item->price) }}</span>
                <span>{{ rupiah($item->subtotal) }}</span>
            </div>
        </div>
    @endforeach

    <div class="rule"></div>

    <table class="sum">
        <tr>
            <td>Subtotal</td>
            <td>{{ rupiah($transaction->subtotal) }}</td>
        </tr>
        @if((float) $transaction->discount_amount > 0)
            <tr>
                <td>Diskon</td>
                <td>&minus;{{ rupiah($transaction->discount_amount) }}</td>
            </tr>
        @endif
        @if((float) $transaction->tax_amount > 0)
            <tr>
                <td>Pajak PB1</td>
                <td>{{ rupiah($transaction->tax_amount) }}</td>
            </tr>
        @endif
        <tr class="total">
            <td>Total</td>
            <td>{{ rupiah($transaction->total) }}</td>
        </tr>
        @if((float) $transaction->paid_amount > 0)
            <tr>
                <td>Dibayar</td>
                <td>{{ rupiah($transaction->paid_amount) }}</td>
            </tr>
        @endif
        @if((float) $transaction->change_amount > 0)
            <tr>
                <td>Kembali</td>
                <td>{{ rupiah($transaction->change_amount) }}</td>
            </tr>
        @endif
    </table>

    <div class="rule"></div>

    <div class="footer">
        {{ \App\Models\Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda') }}
    </div>

    @if($transaction->status === 'void')
        <div class="void">VOID</div>
    @endif
</body>
</html>
