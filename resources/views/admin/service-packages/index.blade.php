<x-layouts.app :title="'Paket Layanan'">
    <div class="topbar">
        <h1>Paket Layanan</h1>
        <a href="{{ route('admin.service-packages.create') }}" class="btn">+ Tambah Paket</a>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Satuan</th>
                    <th>Harga Jual</th>
                    <th>HPP Dasar (Tenaga Kerja + Overhead)</th>
                    <th>Material</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($servicePackages as $package)
                <tr>
                    <td>{{ $package->code }}</td>
                    <td>{{ $package->name }}</td>
                    <td>{{ $package->pricing_unit }}</td>
                    <td>Rp {{ number_format($package->sale_price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($package->labor_cost + $package->overhead_cost, 0, ',', '.') }}</td>
                    <td>{{ $package->materials_count }}</td>
                    <td>{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <div class="row">
                            <a href="{{ route('admin.service-packages.show', $package) }}" class="btn ghost">Detail</a>
                            <a href="{{ route('admin.service-packages.edit', $package) }}" class="btn ghost">Edit</a>
                            <form action="{{ route('admin.service-packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Hapus paket ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada paket layanan.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $servicePackages->links() }}</div>
    </div>
</x-layouts.app>
