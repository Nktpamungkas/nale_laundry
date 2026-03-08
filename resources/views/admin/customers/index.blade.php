<x-layouts.app :title="'Pelanggan - Nale Laundry'">
    <div class="topbar">
        <h1>Data Pelanggan</h1>
        <a href="{{ route('admin.customers.create') }}" class="btn">+ Tambah Pelanggan</a>
    </div>

    <div class="panel">
        <form method="GET" class="row">
            <div class="field" style="flex: 1;">
                <label>Cari</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="Nama / kode / nomor HP">
            </div>
            <button type="submit" class="btn">Filter</button>
        </form>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->code }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->email ?: '-' }}</td>
                    <td>
                        <div class="row">
                            <a class="btn ghost" href="{{ route('admin.customers.show', $customer) }}">Detail</a>
                            <a class="btn ghost" href="{{ route('admin.customers.edit', $customer) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Hapus pelanggan ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada data pelanggan.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $customers->links() }}</div>
    </div>
</x-layouts.app>
