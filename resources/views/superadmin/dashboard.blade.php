<x-layouts.app :title="'Superadmin - Nale Laundry'">
    <div class="topbar">
        <h1>Superadmin</h1>
        <form action="{{ route('superadmin.impersonate') }}" method="POST" class="row" style="gap:8px;">
            @csrf
            <div class="field" style="min-width:240px;">
                <label>Pilih Tenant (impersonasi)</label>
                <select name="tenant_id">
                    <option value="">Mode Global - Semua Tenant</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected($currentTenant && $currentTenant->id === $tenant->id)>
                            {{ $tenant->name }} ({{ $tenant->slug }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn" type="submit">Ganti</button>
        </form>
    </div>

    <div class="grid">
        <div class="card">
            <div class="muted">Total Tenant</div>
            <div style="font-size:28px; font-weight:700;">{{ $totalTenants }}</div>
        </div>
        <div class="card">
            <div class="muted">Total User</div>
            <div style="font-size:28px; font-weight:700;">{{ $totalUsers }}</div>
        </div>
        <div class="card">
            <div class="muted">Order 30 hari</div>
            <div style="font-size:28px; font-weight:700;">
                {{ $orderLast30 }}
            </div>
        </div>
    </div>

    <div class="panel mt-16">
        <h3>Data User</h3>
        <table>
            <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tenant</th>
                <th>Status</th>
                <th>Aktif Terakhir</th>
            </tr>
            </thead>
            <tbody>
            @foreach($allUsers as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="tag">{{ $user->role }}</span></td>
                    <td>{{ $user->tenant?->name ?? 'GLOBAL' }}</td>
                    <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>{{ $user->last_active_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel mt-16">
        <div class="spaced">
            <h3>Kelola Tenant</h3>
            <span class="muted">Klik nama tenant untuk menyaring data lewat impersonasi.</span>
        </div>
        <form class="row mt-8" action="{{ route('superadmin.tenants.store') }}" method="POST">
            @csrf
            <div class="field">
                <label>Nama Tenant</label>
                <input type="text" name="name" required>
            </div>
            <div class="field">
                <label>Slug / Kode</label>
                <input type="text" name="slug" placeholder="contoh: nale-main" required>
            </div>
            <div class="field">
                <label>Plan</label>
                <input type="text" name="plan" placeholder="premium/basic (opsional)">
            </div>
            <button class="btn" type="submit">Tambah Tenant</button>
        </form>
        <table>
            <thead>
            <tr>
                <th>Nama</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Plan</th>
                <th>User</th>
                <th>Order</th>
                <th>Omzet</th>
            </tr>
            </thead>
            <tbody>
            @foreach($tenants as $tenant)
                @php($perf = $tenantPerformance[$tenant->id] ?? null)
                <tr>
                    <td>
                        <form action="{{ route('superadmin.impersonate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                            <button class="btn ghost" type="submit">{{ $tenant->name }}</button>
                        </form>
                    </td>
                    <td>{{ $tenant->slug }}</td>
                    <td><span class="tag">{{ $tenant->status }}</span></td>
                    <td>{{ $tenant->plan ?? '-' }}</td>
                    <td>{{ $tenant->users_count }}</td>
                    <td>{{ $perf->orders_count ?? 0 }}</td>
                    <td>Rp {{ number_format((float) ($perf->omzet ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel mt-16">
        <div class="spaced">
            <h3>Top User Aktif (30 hari)</h3>
            <span class="muted">Berdasarkan frekuensi aktivitas di area admin/superadmin.</span>
        </div>
        <table>
            <thead>
            <tr>
                <th>User</th>
                <th>Tenant</th>
                <th>Hits</th>
                <th>Terakhir Aktif</th>
            </tr>
            </thead>
            <tbody>
            @forelse($activeUsers as $row)
                <tr>
                    <td>{{ $row->user?->name ?? '-' }}<br><span class="muted">{{ $row->user?->email }}</span></td>
                    <td>{{ $row->user?->tenant?->name ?? 'GLOBAL' }}</td>
                    <td>{{ $row->hits }}</td>
                    <td>{{ $row->last_seen?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Belum ada aktivitas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel mt-16">
        <h3>Buat User Tenant</h3>
        <form class="row mt-8" action="{{ route('superadmin.users.store') }}" method="POST">
            @csrf
            <div class="field">
                <label>Tenant</label>
                <select name="tenant_id" required>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->slug }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Nama</label>
                <input type="text" name="name" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>No. HP</label>
                <input type="text" name="phone">
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role" required>
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                    <option value="operator">Operator</option>
                </select>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="text" name="password" placeholder="minimal 6 karakter" required>
            </div>
            <div class="field">
                <label><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
            </div>
            <button class="btn" type="submit">Simpan User</button>
        </form>
    </div>
</x-layouts.app>
