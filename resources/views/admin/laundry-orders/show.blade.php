<x-layouts.app :title="'Detail Order Laundry'">
    <div class="topbar">
        <h1>Detail Order {{ $laundryOrder->order_number }}</h1>
        <div class="row">
            <a class="btn ghost" href="{{ route('admin.laundry-orders.invoice', $laundryOrder) }}">Download Invoice PDF</a>
            <a class="btn ghost" href="{{ route('admin.laundry-orders.receipt-customer', $laundryOrder) }}">Struk Customer (BON)</a>
            <a class="btn ghost" href="{{ route('admin.laundry-orders.receipt-internal', $laundryOrder) }}">Label Internal (BON)</a>
            @if($laundryOrder->status === \App\Support\LaundryStatus::RECEIVED && $laundryOrder->payment_status === 'unpaid')
                <form method="POST" action="{{ route('admin.laundry-orders.destroy', $laundryOrder) }}" onsubmit="return confirm('Hapus order ini? Stok bahan akan dikembalikan.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn danger" type="submit">Hapus Order</button>
                </form>
            @endif
            <a class="btn ghost" href="{{ route('admin.laundry-orders.index') }}">Kembali</a>
        </div>
    </div>

    <div class="grid">
        <div class="card"><div class="muted">Pelanggan</div><strong>{{ $laundryOrder->customer->name }}</strong><div>{{ $laundryOrder->customer->phone }}</div></div>
        <div class="card"><div class="muted">Status</div><strong>{{ $statusLabels[$laundryOrder->status] ?? $laundryOrder->status }}</strong></div>
        <div class="card"><div class="muted">Pembayaran</div><strong>{{ strtoupper($laundryOrder->payment_status) }}</strong></div>
        <div class="card"><div class="muted">Tagihan</div><strong>Rp {{ number_format($laundryOrder->grand_total, 0, ',', '.') }}</strong></div>
        <div class="card"><div class="muted">HPP</div><strong>Rp {{ number_format($laundryOrder->hpp_total, 0, ',', '.') }}</strong></div>
        <div class="card"><div class="muted">Laba Kotor</div><strong>Rp {{ number_format($laundryOrder->grand_total - $laundryOrder->hpp_total, 0, ',', '.') }}</strong></div>
    </div>

    <div class="panel mt-16">
        <h3>Detail Item</h3>
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Qty</th>
                    <th>Harga Jual</th>
                    <th>Subtotal</th>
                    <th>HPP</th>
                    <th>Laba</th>
                </tr>
            </thead>
            <tbody>
            @foreach($laundryOrder->items as $item)
                <tr>
                    <td>{{ $item->servicePackage->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->hpp_total, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->profit_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid">
        @if(auth()->user()?->hasAnyRole(['owner', 'admin', 'kasir', 'operator']))
            <div class="panel">
                <h3>Update Status Proses</h3>
                <form method="POST" action="{{ route('admin.laundry-orders.status', $laundryOrder) }}">
                    @csrf
                    <div class="field">
                        <label>Status Baru</label>
                        <select name="status" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected($laundryOrder->status === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field mt-12">
                        <label>Catatan</label>
                        <textarea name="note" rows="3"></textarea>
                    </div>
                    <button class="btn mt-12" type="submit">Simpan Status</button>
                </form>
            </div>
        @endif

        @if(auth()->user()?->hasAnyRole(['owner', 'admin', 'kasir']))
            <div class="panel">
                <h3>Tambah Pembayaran</h3>
                <form method="POST" action="{{ route('admin.laundry-orders.payments', $laundryOrder) }}">
                    @csrf
                    <div class="field">
                        <label>Tanggal Bayar</label>
                        <input type="datetime-local" name="payment_date" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                    </div>
                    <div class="field mt-12">
                        <label>Jumlah</label>
                        <input type="text" inputmode="numeric" name="amount" data-format="grouped-number" data-decimals="0" required>
                    </div>
                    <div class="field mt-12">
                        <label>Metode</label>
                        <select name="method" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                            <option value="edc">EDC</option>
                        </select>
                    </div>
                    <div class="field mt-12">
                        <label>No Referensi</label>
                        <input type="text" name="reference_no">
                    </div>
                    <button class="btn mt-12" type="submit">Simpan Pembayaran</button>
                </form>
            </div>
        @endif
    </div>

    <div class="panel">
        <h3>Timeline Status</h3>
        <table>
            <thead><tr><th>Waktu</th><th>Status</th><th>Petugas</th><th>Catatan</th></tr></thead>
            <tbody>
            @forelse($laundryOrder->statusHistories as $history)
                <tr>
                    <td>{{ $history->changed_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $statusLabels[$history->status] ?? $history->status }}</td>
                    <td>{{ $history->changer->name ?? '-' }}</td>
                    <td>{{ $history->note ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada histori status.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Riwayat Pembayaran</h3>
        <table>
            <thead><tr><th>Tanggal</th><th>Metode</th><th>Jumlah</th><th>Referensi</th><th>Petugas</th></tr></thead>
            <tbody>
            @forelse($laundryOrder->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date?->format('d/m/Y H:i') }}</td>
                    <td>{{ strtoupper($payment->method) }}</td>
                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td>{{ $payment->reference_no ?: '-' }}</td>
                    <td>{{ $payment->receiver->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada pembayaran.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Log Notifikasi WhatsApp</h3>
        <table>
            <thead><tr><th>Waktu</th><th>Nomor</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
            @forelse($laundryOrder->whatsappNotifications->sortByDesc('created_at') as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->phone }}</td>
                    <td>
                        @if($log->is_success)
                            <span class="tag">Terkirim</span>
                        @else
                            <span class="tag" style="background:#fee2e2;color:#991b1b;">Gagal/Skip</span>
                        @endif
                    </td>
                    <td>{{ $log->error_message ?: 'HTTP '.$log->response_status }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada notifikasi WhatsApp.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
