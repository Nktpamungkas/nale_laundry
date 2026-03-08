<x-layouts.app :title="'Inventory Items'">
    @php
        $formatQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="topbar">
        <h1>Inventory Items</h1>
        <div class="row">
            <a class="btn ghost" href="{{ route('admin.inventory-items.index', ['low' => 1]) }}">Lihat Low Stock</a>
            <a class="btn" href="{{ route('admin.inventory-items.create') }}">+ Tambah Item</a>
        </div>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Min</th>
                    <th>Avg Cost</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($inventoryItems as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category ?: '-' }}</td>
                    <td>{{ $formatQty($item->current_stock) }} {{ $item->unit }}</td>
                    <td>{{ $formatQty($item->minimum_stock) }} {{ $item->unit }}</td>
                    <td>Rp {{ number_format($item->average_cost, 0, ',', '.') }}</td>
                    <td>
                        @if($item->current_stock <= $item->minimum_stock)
                            <span class="tag" style="background:#fee2e2;color:#991b1b;">Low</span>
                        @else
                            <span class="tag">Aman</span>
                        @endif
                    </td>
                    <td>
                        <div class="row">
                            <a class="btn ghost" href="{{ route('admin.inventory-items.show', $item) }}">Detail</a>
                            <a class="btn ghost" href="{{ route('admin.inventory-items.edit', $item) }}">Edit</a>
                            <form action="{{ route('admin.inventory-items.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus item ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada item inventory.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $inventoryItems->links() }}</div>
    </div>
</x-layouts.app>
