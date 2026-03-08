<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $laundryOrder->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { display: table; width: 100%; margin-bottom: 16px; }
        .header .left, .header .right { display: table-cell; vertical-align: top; }
        .header .right { text-align: right; }
        h1 { margin: 0; font-size: 20px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
        .summary { margin-top: 12px; width: 45%; margin-left: auto; }
        .summary td { border: 0; padding: 4px 0; }
        .summary .line td { border-top: 1px solid #d1d5db; font-weight: bold; }
        .footer { margin-top: 18px; font-size: 11px; color: #6b7280; }
        .status { display: inline-block; padding: 4px 8px; border: 1px solid #9ca3af; border-radius: 999px; }
        .qr { margin-top: 10px; text-align: right; }
        .qr img { width: 110px; height: 110px; }
        .qr .code { font-size: 11px; letter-spacing: 1px; margin-top: 2px; }
    </style>
</head>
<body>
<div class="header">
    <div class="left">
        <h1>Nale Laundry</h1>
        <div class="muted">Jl. Contoh Alamat Laundry No. 1</div>
        <div class="muted">Telp: 0812-3456-7890</div>
    </div>
    <div class="right">
        <h2 style="margin:0;">INVOICE</h2>
        <div><strong>{{ $laundryOrder->order_number }}</strong></div>
        <div>{{ $laundryOrder->created_at?->format('d/m/Y H:i') }}</div>
        <div class="status">{{ $statusLabels[$laundryOrder->status] ?? strtoupper($laundryOrder->status) }}</div>
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="QR {{ $laundryOrder->order_number }}">
            <div class="code">{{ $laundryOrder->order_number }}</div>
        </div>
    </div>
</div>

<table>
    <tr>
        <th style="width: 20%;">Pelanggan</th>
        <td>{{ $laundryOrder->customer->name }}</td>
        <th style="width: 20%;">No HP</th>
        <td>{{ $laundryOrder->customer->phone }}</td>
    </tr>
    <tr>
        <th>Alamat</th>
        <td>{{ $laundryOrder->customer->address ?: '-' }}</td>
        <th>Estimasi Selesai</th>
        <td>{{ $laundryOrder->due_at?->format('d/m/Y H:i') ?? '-' }}</td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th>Layanan</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Harga</th>
        <th class="text-right">Subtotal</th>
    </tr>
    </thead>
    <tbody>
    @foreach($laundryOrder->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->servicePackage->name }} @if($item->description)- {{ $item->description }} @endif</td>
            <td class="text-right">{{ number_format($item->quantity, 3, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="summary">
    <tr><td>Subtotal</td><td class="text-right">Rp {{ number_format($laundryOrder->subtotal, 0, ',', '.') }}</td></tr>
    <tr><td>Diskon</td><td class="text-right">Rp {{ number_format($laundryOrder->discount_amount, 0, ',', '.') }}</td></tr>
    <tr class="line"><td>Total</td><td class="text-right">Rp {{ number_format($laundryOrder->grand_total, 0, ',', '.') }}</td></tr>
    <tr><td>Sudah Dibayar</td><td class="text-right">Rp {{ number_format($laundryOrder->paid_amount, 0, ',', '.') }}</td></tr>
    <tr class="line"><td>Sisa Tagihan</td><td class="text-right">Rp {{ number_format(max((float)$laundryOrder->grand_total - (float)$laundryOrder->paid_amount, 0), 0, ',', '.') }}</td></tr>
</table>

<div class="footer">
    <div>Catatan: {{ $laundryOrder->status_note ?: '-' }}</div>
    <div>Terima kasih telah menggunakan layanan Nale Laundry.</div>
</div>
</body>
</html>
