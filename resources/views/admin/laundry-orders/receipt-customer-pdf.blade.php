<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Customer {{ $laundryOrder->order_number }}</title>
    <style>
        @page { margin: 6mm 5mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }
        .center { text-align: center; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .muted { color: #6b7280; }
        .line { border-top: 1px dashed #9ca3af; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 0; vertical-align: top; }
        th { text-align: left; font-weight: bold; border-bottom: 1px solid #d1d5db; }
        .text-right { text-align: right; }
        .totals td { padding: 2px 0; }
        .qr { margin-top: 8px; text-align: center; }
        .qr img { width: 120px; height: 120px; }
        .code { letter-spacing: 1px; margin-top: 2px; font-size: 9px; }
        .small { font-size: 9px; }
    </style>
</head>
<body>
<div class="center">
    <div class="title">NALE LAUNDRY</div>
    <div class="small">Struk Pelanggan</div>
    <div class="muted">{{ $laundryOrder->created_at?->format('d/m/Y H:i') }}</div>
</div>

<div class="line"></div>

<table>
    <tr><td>No Order</td><td class="text-right"><strong>{{ $laundryOrder->order_number }}</strong></td></tr>
    <tr><td>Pelanggan</td><td class="text-right">{{ $laundryOrder->customer->name }}</td></tr>
    <tr><td>No HP</td><td class="text-right">{{ $laundryOrder->customer->phone }}</td></tr>
    <tr><td>Status</td><td class="text-right">{{ $statusLabels[$laundryOrder->status] ?? strtoupper($laundryOrder->status) }}</td></tr>
    <tr><td>Estimasi</td><td class="text-right">{{ $laundryOrder->due_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
</table>

<div class="line"></div>

<table>
    <thead>
    <tr>
        <th>Layanan</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Subtotal</th>
    </tr>
    </thead>
    <tbody>
    @foreach($laundryOrder->items as $item)
        <tr>
            <td>{{ $item->servicePackage->name }}</td>
            <td class="text-right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format($item->line_total, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="line"></div>

<table class="totals">
    <tr><td>Subtotal</td><td class="text-right">Rp {{ number_format($laundryOrder->subtotal, 0, ',', '.') }}</td></tr>
    <tr><td>Diskon</td><td class="text-right">Rp {{ number_format($laundryOrder->discount_amount, 0, ',', '.') }}</td></tr>
    <tr><td><strong>Total</strong></td><td class="text-right"><strong>Rp {{ number_format($laundryOrder->grand_total, 0, ',', '.') }}</strong></td></tr>
    <tr><td>Terbayar</td><td class="text-right">Rp {{ number_format($laundryOrder->paid_amount, 0, ',', '.') }}</td></tr>
    <tr><td><strong>Sisa</strong></td><td class="text-right"><strong>Rp {{ number_format(max((float)$laundryOrder->grand_total - (float)$laundryOrder->paid_amount, 0), 0, ',', '.') }}</strong></td></tr>
</table>

<div class="qr">
    <img src="{{ $qrDataUri }}" alt="QR {{ $laundryOrder->order_number }}">
    <div class="code">{{ $laundryOrder->order_number }}</div>
</div>

<div class="center small" style="margin-top:8px;">
    Tunjukkan struk ini saat pengambilan laundry.
</div>
</body>
</html>
