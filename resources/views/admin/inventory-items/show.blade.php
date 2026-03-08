<x-layouts.app :title="'Detail Inventory Item'">
    @php
        $formatQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="topbar">
        <h1>Detail Inventory Item</h1>
        <a href="{{ route('admin.inventory-items.edit', $inventoryItem) }}" class="btn">Edit</a>
    </div>

    <div class="panel">
        <div class="grid">
            <div class="card"><div class="muted">SKU</div><strong>{{ $inventoryItem->sku }}</strong></div>
            <div class="card"><div class="muted">Nama</div><strong>{{ $inventoryItem->name }}</strong></div>
            <div class="card"><div class="muted">Kategori</div><strong>{{ $inventoryItem->category ?: '-' }}</strong></div>
            <div class="card"><div class="muted">Stok</div><strong>{{ $formatQty($inventoryItem->current_stock) }} {{ $inventoryItem->unit }}</strong></div>
            <div class="card"><div class="muted">Minimum</div><strong>{{ $formatQty($inventoryItem->minimum_stock) }} {{ $inventoryItem->unit }}</strong></div>
            <div class="card"><div class="muted">Avg Cost</div><strong>Rp {{ number_format($inventoryItem->average_cost, 0, ',', '.') }}</strong></div>
        </div>
    </div>

    <div class="panel">
        <h3>Mutasi Terakhir</h3>
        <table>
            <thead><tr><th>Tanggal</th><th>Tipe</th><th>Masuk</th><th>Keluar</th><th>Biaya</th><th>Catatan</th></tr></thead>
            <tbody>
            @forelse($inventoryItem->stockMovements as $movement)
                <tr>
                    <td>{{ $movement->movement_date?->format('d/m/Y H:i') }}</td>
                    <td>{{ $movement->movement_type }}</td>
                    <td>{{ $formatQty($movement->quantity_in) }}</td>
                    <td>{{ $formatQty($movement->quantity_out) }}</td>
                    <td>Rp {{ number_format($movement->unit_cost, 0, ',', '.') }}</td>
                    <td>{{ $movement->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada mutasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
