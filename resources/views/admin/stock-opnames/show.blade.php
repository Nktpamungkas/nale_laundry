<x-layouts.app :title="'Detail Stok Opname'">
    <div class="topbar">
        <h1>{{ $stockOpname->opname_number }}</h1>
        <a href="{{ route('admin.stock-opnames.index') }}" class="btn ghost">Kembali</a>
    </div>

    <div class="grid">
        <div class="card"><div class="muted">Tanggal</div><strong>{{ $stockOpname->opname_date?->format('d/m/Y') }}</strong></div>
        <div class="card"><div class="muted">Status</div><strong>{{ strtoupper($stockOpname->status) }}</strong></div>
        <div class="card"><div class="muted">Dibuat Oleh</div><strong>{{ $stockOpname->creator->name ?? '-' }}</strong></div>
        <div class="card"><div class="muted">Diposting Oleh</div><strong>{{ $stockOpname->approver->name ?? '-' }}</strong></div>
    </div>

    <div class="panel mt-16">
        @if($stockOpname->status === 'draft')
            <form action="{{ route('admin.stock-opnames.update', $stockOpname) }}" method="POST">
                @csrf
                @method('PUT')
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>System Stock</th>
                            <th>Actual Stock</th>
                            <th>Selisih</th>
                            <th>Nilai Penyesuaian</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($stockOpname->items as $item)
                        <tr>
                            <td>{{ $item->inventoryItem->name }} ({{ $item->inventoryItem->unit }})</td>
                            <td>{{ $item->system_stock }}</td>
                            <td><input type="number" step="0.001" min="0" name="actual_stock[{{ $item->id }}]" value="{{ old('actual_stock.'.$item->id, $item->actual_stock) }}" required></td>
                            <td>{{ $item->difference_stock }}</td>
                            <td>Rp {{ number_format($item->adjustment_cost, 0, ',', '.') }}</td>
                            <td><input type="text" name="notes[{{ $item->id }}]" value="{{ old('notes.'.$item->id, $item->notes) }}"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="row mt-12">
                    <button class="btn" type="submit">Simpan Draft</button>
                    <button class="btn warn" type="submit" name="post_after_save" value="1" onclick="return confirm('Simpan dan posting opname?')">Simpan & Posting</button>
                </div>
            </form>

            <form class="mt-12" action="{{ route('admin.stock-opnames.post', $stockOpname) }}" method="POST" onsubmit="return confirm('Posting opname sekarang?')">
                @csrf
                <button class="btn" type="submit">Posting Sekarang</button>
            </form>
            <form class="mt-12" action="{{ route('admin.stock-opnames.destroy', $stockOpname) }}" method="POST" onsubmit="return confirm('Hapus draft opname ini?')">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit">Hapus Draft</button>
            </form>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>System</th>
                        <th>Actual</th>
                        <th>Selisih</th>
                        <th>Nilai</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($stockOpname->items as $item)
                    <tr>
                        <td>{{ $item->inventoryItem->name }} ({{ $item->inventoryItem->unit }})</td>
                        <td>{{ $item->system_stock }}</td>
                        <td>{{ $item->actual_stock }}</td>
                        <td>{{ $item->difference_stock }}</td>
                        <td>Rp {{ number_format($item->adjustment_cost, 0, ',', '.') }}</td>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-layouts.app>
