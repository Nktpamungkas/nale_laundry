<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label Internal {{ $laundryOrder->order_number }}</title>
    <style>
        @page { margin: 6mm 5mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }
        .center { text-align: center; }
        .title { font-size: 13px; font-weight: bold; }
        .line { border-top: 1px dashed #9ca3af; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        .text-right { text-align: right; }
        .qr { margin-top: 8px; text-align: center; }
        .qr img { width: 130px; height: 130px; }
        .code { font-size: 9px; letter-spacing: 1px; margin-top: 2px; }
        .box {
            border: 1px solid #111827;
            padding: 6px;
            margin-top: 8px;
        }
        .station td {
            border-bottom: 1px dotted #9ca3af;
            padding: 4px 0;
        }
    </style>
</head>
<body>
<div class="center">
    <div class="title">LABEL INTERNAL LAUNDRY</div>
    <div>{{ $laundryOrder->created_at?->format('d/m/Y H:i') }}</div>
</div>

<div class="box">
    <table>
        <tr><td><strong>No Order</strong></td><td class="text-right"><strong>{{ $laundryOrder->order_number }}</strong></td></tr>
        <tr><td>Pelanggan</td><td class="text-right">{{ $laundryOrder->customer->name }}</td></tr>
        <tr><td>No HP</td><td class="text-right">{{ $laundryOrder->customer->phone }}</td></tr>
        <tr><td>Status Saat Ini</td><td class="text-right">{{ $statusLabels[$laundryOrder->status] ?? strtoupper($laundryOrder->status) }}</td></tr>
    </table>
</div>

<div class="qr">
    <img src="{{ $qrDataUri }}" alt="QR {{ $laundryOrder->order_number }}">
    <div class="code">{{ $laundryOrder->order_number }}</div>
</div>

<div class="line"></div>

<table class="station">
    <tr><td>[ ] Cuci</td><td class="text-right">Paraf: ______</td></tr>
    <tr><td>[ ] Kering</td><td class="text-right">Paraf: ______</td></tr>
    <tr><td>[ ] Gosok</td><td class="text-right">Paraf: ______</td></tr>
    <tr><td>[ ] Packing</td><td class="text-right">Paraf: ______</td></tr>
    <tr><td>[ ] Siap Ambil</td><td class="text-right">Paraf: ______</td></tr>
</table>

<div class="center" style="margin-top:8px; font-size:9px;">
    Scan QR ini untuk update status di station.
</div>
</body>
</html>
