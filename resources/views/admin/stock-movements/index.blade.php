<x-layouts.app :title="'Mutasi Stok'">
    @php
        $formatQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="topbar">
        <h1>Mutasi Stok</h1>
        <a href="{{ route('admin.stock-movements.create') }}" class="btn">+ Catat Mutasi</a>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Item</th>
                    <th>Tipe</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Biaya Satuan</th>
                    <th>Nilai</th>
                    <th>Sumber Dana</th>
                    <th>Petugas</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
            @forelse($stockMovements as $movement)
                <tr>
                    <td>{{ $movement->movement_date?->format('d/m/Y H:i') }}</td>
                    <td>{{ $movement->inventoryItem->name }}</td>
                    <td>{{ $movement->movement_type }}</td>
                    <td>{{ $formatQty($movement->quantity_in) }}</td>
                    <td>{{ $formatQty($movement->quantity_out) }}</td>
                    <td>Rp {{ number_format($movement->unit_cost, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($movement->total_cost, 0, ',', '.') }}</td>
                    <td>
                        @if($movement->movement_type === 'purchase')
                            @php
                                $sourceLabel = match($movement->funding_source) {
                                    'dana_owner' => 'Dana Owner',
                                    'hutang_supplier' => 'Hutang Supplier',
                                    default => 'Kas Toko',
                                };
                            @endphp
                            {{ $sourceLabel }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $movement->creator->name ?? '-' }}</td>
                    <td>{{ $movement->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Belum ada mutasi stok.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $stockMovements->links() }}</div>
    </div>
</x-layouts.app>
