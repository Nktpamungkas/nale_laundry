<x-layouts.app :title="'Detail Pelanggan'">
    <div class="topbar">
        <h1>Detail Pelanggan</h1>
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn">Edit</a>
    </div>

    <div class="panel">
        <div class="grid">
            <div class="card"><div class="muted">Kode</div><strong>{{ $customer->code }}</strong></div>
            <div class="card"><div class="muted">Nama</div><strong>{{ $customer->name }}</strong></div>
            <div class="card"><div class="muted">No. HP</div><strong>{{ $customer->phone }}</strong></div>
            <div class="card"><div class="muted">Email</div><strong>{{ $customer->email ?: '-' }}</strong></div>
        </div>
        <div class="mt-12">
            <div class="muted">Alamat</div>
            <p>{{ $customer->address ?: '-' }}</p>
        </div>
    </div>

    <div class="panel">
        <h3>Riwayat 20 Order Terakhir</h3>
        <table>
            <thead><tr><th>No. Order</th><th>Status</th><th>Total</th><th>Tanggal</th><th></th></tr></thead>
            <tbody>
            @forelse($customer->laundryOrders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->status }}</td>
                    <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td><a class="btn ghost" href="{{ route('admin.laundry-orders.show', $order) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada order.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
