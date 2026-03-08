<x-layouts.app :title="'Order Laundry'">
    <div class="topbar">
        <h1>Order Laundry</h1>
        <a href="{{ route('admin.laundry-orders.create') }}" class="btn">+ Buat Order</a>
    </div>

    <div class="panel">
        <form method="GET" class="row">
            <div class="field">
                <label>Status Proses</label>
                <select name="status">
                    <option value="">Semua Status</option>
                    @foreach($statusLabels as $code => $label)
                        <option value="{{ $code }}" @selected($status === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Status Pembayaran</label>
                <select name="payment_status">
                    <option value="">Semua</option>
                    <option value="unpaid" @selected($paymentStatus === 'unpaid')>Belum Bayar</option>
                    <option value="partial" @selected($paymentStatus === 'partial')>Sebagian</option>
                    <option value="paid" @selected($paymentStatus === 'paid')>Lunas</option>
                </select>
            </div>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Pelanggan</th>
                    <th>Status Proses</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                    <th>Diterima</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($laundryOrders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->customer->name }}</td>
                    <td><span class="tag status-badge">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                    <td>
                        @if($order->payment_status === 'paid')
                            <span class="tag">Lunas</span>
                        @elseif($order->payment_status === 'partial')
                            <span class="tag" style="background:#fef3c7;color:#92400e;">Sebagian</span>
                        @else
                            <span class="tag" style="background:#fee2e2;color:#991b1b;">Belum Bayar</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                    <td>{{ $order->received_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="row">
                            <a class="btn ghost" href="{{ route('admin.laundry-orders.show', $order) }}">Detail</a>
                            <a class="btn ghost" href="{{ route('admin.laundry-orders.edit', $order) }}">Edit</a>
                            @if($order->status === \App\Support\LaundryStatus::RECEIVED && $order->payment_status === 'unpaid')
                                <form method="POST" action="{{ route('admin.laundry-orders.destroy', $order) }}" onsubmit="return confirm('Hapus order ini? Stok bahan akan dikembalikan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada order laundry.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-12">{{ $laundryOrders->links() }}</div>
    </div>
</x-layouts.app>
