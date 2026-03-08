<x-layouts.app :title="'Dashboard - Nale Laundry'">
    @php
        $formatQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="topbar">
        <h1>Dashboard</h1>
        <a href="{{ route('admin.laundry-orders.create') }}" class="btn">+ Order Baru</a>
    </div>

    <div class="grid">
        <div class="card">
            <div class="muted">Total Order</div>
            <h2>{{ number_format($totalOrders) }}</h2>
        </div>
        <div class="card">
            <div class="muted">Order Aktif</div>
            <h2>{{ number_format($inProgressOrders) }}</h2>
        </div>
        <div class="card">
            <div class="muted">Omzet Bulan Ini</div>
            <h2>Rp {{ number_format($monthSales, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">HPP Bulan Ini</div>
            <h2>Rp {{ number_format($monthHpp, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">Laba Kotor Bulan Ini</div>
            <h2>Rp {{ number_format($monthGrossProfit, 0, ',', '.') }}</h2>
        </div>
        <div class="card">
            <div class="muted">Pembayaran Hari Ini</div>
            <h2>Rp {{ number_format($todayRevenue, 0, ',', '.') }}</h2>
        </div>
    </div>

    <div class="panel mt-16">
        <h3>Status Order</h3>
        <div class="grid">
            @foreach($statusLabels as $code => $label)
                <div class="card">
                    <div class="muted">{{ $label }}</div>
                    <strong>{{ $statusCounts[$code] ?? 0 }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <div class="spaced">
            <h3 class="mb-0">Order Terbaru</h3>
            <a href="{{ route('admin.laundry-orders.index') }}" class="btn ghost">Lihat Semua</a>
        </div>
        <table class="mt-12">
            <thead>
            <tr>
                <th>No. Order</th>
                <th>Pelanggan</th>
                <th>Status</th>
                <th>Total</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($recentOrders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->customer->name }}</td>
                    <td><span class="tag status-badge">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                    <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                    <td><a href="{{ route('admin.laundry-orders.show', $order) }}" class="btn ghost">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada order.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Stok Menipis</h3>
        <table>
            <thead>
            <tr>
                <th>SKU</th>
                <th>Item</th>
                <th>Stok Saat Ini</th>
                <th>Minimum</th>
            </tr>
            </thead>
            <tbody>
            @forelse($lowStockItems as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $formatQty($item->current_stock) }} {{ $item->unit }}</td>
                    <td>{{ $formatQty($item->minimum_stock) }} {{ $item->unit }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada item low stock.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
