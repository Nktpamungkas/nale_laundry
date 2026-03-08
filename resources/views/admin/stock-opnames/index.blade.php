<x-layouts.app :title="'Stok Opname'">
    <div class="topbar">
        <h1>Stok Opname</h1>
        <a href="{{ route('admin.stock-opnames.create') }}" class="btn">+ Buat Draft Opname</a>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>No. Opname</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Jumlah Item</th>
                    <th>Petugas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($stockOpnames as $opname)
                <tr>
                    <td>{{ $opname->opname_number }}</td>
                    <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                    <td>
                        @if($opname->status === 'posted')
                            <span class="tag">POSTED</span>
                        @else
                            <span class="tag" style="background:#fef3c7;color:#92400e;">DRAFT</span>
                        @endif
                    </td>
                    <td>{{ $opname->items_count }}</td>
                    <td>{{ $opname->creator->name ?? '-' }}</td>
                    <td><a href="{{ route('admin.stock-opnames.show', $opname) }}" class="btn ghost">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada data stok opname.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $stockOpnames->links() }}</div>
    </div>
</x-layouts.app>
