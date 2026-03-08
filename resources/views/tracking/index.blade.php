<x-layouts.app :title="'Cek Status Laundry - Nale Laundry'">
    <div class="topbar">
        <h1>Cek Status Laundry</h1>
        <a class="btn ghost" href="{{ route('login') }}">Login Admin</a>
    </div>

    <div class="panel" style="max-width: 780px; margin-inline: auto;">
        <form action="{{ route('tracking.track') }}" method="POST" class="row">
            @csrf
            <div class="field" style="flex: 1;">
                <label>No. Order</label>
                <input type="text" name="order_number" value="{{ old('order_number') }}" placeholder="Contoh: ORD-20260228-0001" required>
            </div>
            <div class="field" style="flex: 1;">
                <label>No. HP Pelanggan</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" required>
            </div>
            <div class="field">
                <button class="btn" type="submit">Cek Status</button>
            </div>
        </form>

        @if($order)
            <div class="panel mt-16" style="margin-bottom: 0; background: #f8fafc;">
                <div class="spaced">
                    <div>
                        <h3 style="margin: 0;">{{ $order->order_number }}</h3>
                        <p class="muted mb-0">{{ $order->customer->name }} - {{ $order->customer->phone }}</p>
                    </div>
                    <span class="tag">{{ $statusLabels[$order->status] ?? strtoupper($order->status) }}</span>
                </div>

                <div class="grid mt-12">
                    <div class="card"><div class="muted">Diterima</div><strong>{{ $order->received_at?->format('d/m/Y H:i') }}</strong></div>
                    <div class="card"><div class="muted">Estimasi Selesai</div><strong>{{ $order->due_at?->format('d/m/Y H:i') ?? '-' }}</strong></div>
                    <div class="card"><div class="muted">Total Tagihan</div><strong>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</strong></div>
                    <div class="card"><div class="muted">Sudah Dibayar</div><strong>Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</strong></div>
                </div>

                <h4 class="mt-16">Timeline Proses</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($order->statusHistories as $history)
                        <tr>
                            <td>{{ $history->changed_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $statusLabels[$history->status] ?? $history->status }}</td>
                            <td>{{ $history->note ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
