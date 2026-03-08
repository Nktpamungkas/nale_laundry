<x-layouts.app :title="'Detail Paket Layanan'">
    <div class="topbar">
        <h1>Detail Paket Layanan</h1>
        <a href="{{ route('admin.service-packages.edit', $servicePackage) }}" class="btn">Edit</a>
    </div>

    <div class="panel">
        <div class="grid">
            <div class="card"><div class="muted">Kode</div><strong>{{ $servicePackage->code }}</strong></div>
            <div class="card"><div class="muted">Nama</div><strong>{{ $servicePackage->name }}</strong></div>
            <div class="card"><div class="muted">Satuan</div><strong>{{ $servicePackage->pricing_unit }}</strong></div>
            <div class="card"><div class="muted">Harga Jual</div><strong>Rp {{ number_format($servicePackage->sale_price, 0, ',', '.') }}</strong></div>
            <div class="card"><div class="muted">Biaya Tenaga Kerja (Estimasi HPP)</div><strong>Rp {{ number_format($servicePackage->labor_cost, 0, ',', '.') }}</strong></div>
            <div class="card"><div class="muted">Biaya Overhead (Estimasi HPP)</div><strong>Rp {{ number_format($servicePackage->overhead_cost, 0, ',', '.') }}</strong></div>
        </div>
        <p class="mt-12">{{ $servicePackage->description ?: '-' }}</p>
    </div>

    <div class="panel">
        <h3>Resep Material</h3>
        <table>
            <thead><tr><th>Item</th><th>Qty per Satuan</th><th>Waste %</th><th>Biaya/unit</th></tr></thead>
            <tbody>
            @forelse($servicePackage->materials as $material)
                <tr>
                    <td>{{ $material->inventoryItem->name }}</td>
                    <td>{{ $material->quantity_per_unit }} {{ $material->inventoryItem->unit }}</td>
                    <td>{{ $material->waste_percent }}%</td>
                    <td>Rp {{ number_format(($material->quantity_per_unit * (1 + ($material->waste_percent/100))) * $material->inventoryItem->average_cost, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada material.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
