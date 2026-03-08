<x-layouts.app :title="'Laporan Bulanan'">
    <div class="topbar">
        <h1>Laporan Bulanan</h1>
    </div>

    <div class="panel">
        <form method="GET" class="row">
            <div class="field">
                <label>Pilih Bulan</label>
                <input type="month" name="month" value="{{ request('month', $selectedMonth->format('Y-m')) }}">
            </div>
            <button class="btn" type="submit">Tampilkan</button>
        </form>
    </div>

    <div class="grid">
        <div class="card"><div class="muted">Total Order</div><h2>{{ number_format($totalOrders) }}</h2></div>
        <div class="card"><div class="muted">Omzet</div><h2>Rp {{ number_format($totalSales, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">HPP</div><h2>Rp {{ number_format($totalHpp, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Laba Kotor</div><h2>Rp {{ number_format($grossProfit, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Margin Kotor</div><h2>{{ number_format($grossMargin, 2, ',', '.') }}%</h2></div>
        <div class="card"><div class="muted">Pembayaran Diterima</div><h2>Rp {{ number_format($paymentsReceived, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Belanja Inventory (Total)</div><h2>Rp {{ number_format($inventoryPurchases, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Belanja Inventory dari Kas Toko</div><h2>Rp {{ number_format($inventoryPurchasesFromStoreCash, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Sisa Kas untuk Bagi Hasil</div><h2 style="color: {{ $distributableCash >= 0 ? '#166534' : '#991b1b' }};">Rp {{ number_format($distributableCash, 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Piutang Bulan Ini</div><h2>Rp {{ number_format($outstanding, 0, ',', '.') }}</h2></div>
    </div>

    <div class="panel mt-16">
        <h3>Belanja Inventory per Sumber Dana</h3>
        <table>
            <thead>
                <tr>
                    <th>Sumber Dana</th>
                    <th>Total Belanja</th>
                </tr>
            </thead>
            <tbody>
            @forelse($purchaseByFundingSource as $row)
                <tr>
                    <td>{{ $row['funding_source_label'] }}</td>
                    <td>Rp {{ number_format($row['total_cost'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Belum ada pembelian inventory pada bulan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel mt-16">
        <div class="spaced">
            <h3 class="mb-0">Simulasi Bagi Hasil Pendapatan</h3>
            <button type="button" class="btn ghost" id="add-share-row">+ Tambah Penerima</button>
        </div>
        <p class="muted mt-8 mb-0">Dasar perhitungan: Sisa Kas untuk Bagi Hasil (Rp {{ number_format($shareBaseAmount, 0, ',', '.') }}) = Pembayaran Diterima - Belanja Inventory dari Kas Toko.</p>
        @if($distributableCash < 0)
            <div class="alert warning mt-12 mb-0">Kas toko bulan ini minus Rp {{ number_format(abs($distributableCash), 0, ',', '.') }} setelah belanja inventory. Simulasi bagi hasil dihitung dari 0.</div>
        @endif

        <form method="GET" class="mt-12" id="share-form">
            <input type="hidden" name="month" value="{{ request('month', $selectedMonth->format('Y-m')) }}">
            <table id="share-table">
                <thead>
                    <tr>
                        <th>Nama Penerima</th>
                        <th>Persen (%)</th>
                        <th>Nominal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($shareRows as $row)
                    <tr>
                        <td><input type="text" name="share_names[]" value="{{ $row['name'] }}" required></td>
                        <td><input type="number" step="0.01" min="0" name="share_percents[]" value="{{ number_format($row['percent'], 2, '.', '') }}" required></td>
                        <td>Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                        <td><button type="button" class="btn danger share-remove">x</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="row mt-12">
                <div class="card" style="min-width:220px;">
                    <div class="muted">Total Persentase</div>
                    <strong style="color: {{ abs($totalSharePercent - 100) < 0.01 ? '#166534' : '#991b1b' }};">
                        {{ number_format($totalSharePercent, 2, ',', '.') }}%
                    </strong>
                </div>
                <div class="card" style="min-width:220px;">
                    <div class="muted">Total Nominal Bagi Hasil</div>
                    <strong>Rp {{ number_format($totalShareAmount, 0, ',', '.') }}</strong>
                </div>
            </div>
            @if(abs($totalSharePercent - 100) >= 0.01)
                <div class="alert warning mt-12 mb-0">Total persentase belum 100%. Silakan sesuaikan agar pembagian pas.</div>
            @endif
            <button class="btn mt-12" type="submit">Hitung Ulang</button>
        </form>
    </div>

    <div class="panel mt-16">
        <h3>Status Order Bulan {{ $selectedMonth->translatedFormat('F Y') }}</h3>
        <div class="grid">
            @foreach($statusLabels as $code => $label)
                <div class="card">
                    <div class="muted">{{ $label }}</div>
                    <strong>{{ $statusCounts[$code] ?? 0 }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <h3>Pendapatan per Karyawan</h3>
        <table>
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Diterima</th>
                    <th>Rata-rata per Transaksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employeeRevenue as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ number_format($row['total_transactions']) }}</td>
                    <td>Rp {{ number_format($row['total_amount'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row['avg_per_transaction'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada pembayaran di bulan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Breakdown Paket Layanan</h3>
        <table>
            <thead>
                <tr>
                    <th>Paket</th>
                    <th>Total Qty</th>
                    <th>Total Penjualan</th>
                    <th>Total HPP</th>
                    <th>Laba Kotor</th>
                    <th>Margin</th>
                </tr>
            </thead>
            <tbody>
            @forelse($packageBreakdown as $row)
                @php
                    $profit = (float)$row->total_sales - (float)$row->total_hpp;
                    $margin = (float)$row->total_sales > 0 ? ($profit / (float)$row->total_sales) * 100 : 0;
                @endphp
                <tr>
                    <td>{{ $row->package_name }}</td>
                    <td>{{ number_format($row->total_qty, 3, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->total_sales, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->total_hpp, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($profit, 0, ',', '.') }}</td>
                    <td>{{ number_format($margin, 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada data transaksi pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Tren 6 Bulan Terakhir</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Penjualan</th>
                    <th>HPP</th>
                    <th>Laba Kotor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($trend as $item)
                <tr>
                    <td>{{ $item['month'] }}</td>
                    <td>Rp {{ number_format($item['sales'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item['hpp'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item['profit'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

<script>
(function () {
    const addRowBtn = document.getElementById('add-share-row');
    const tableBody = document.querySelector('#share-table tbody');

    if (!addRowBtn || !tableBody) {
        return;
    }

    addRowBtn.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="share_names[]" value="" placeholder="Nama penerima" required></td>
            <td><input type="number" step="0.01" min="0" name="share_percents[]" value="0" required></td>
            <td>Rp 0</td>
            <td><button type="button" class="btn danger share-remove">x</button></td>
        `;
        tableBody.appendChild(row);
    });

    tableBody.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (!target.classList.contains('share-remove')) {
            return;
        }

        const row = target.closest('tr');
        if (!row) {
            return;
        }

        if (tableBody.querySelectorAll('tr').length <= 1) {
            return;
        }

        row.remove();
    });
})();
</script>
</x-layouts.app>
