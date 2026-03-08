<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Nale Laundry' }}</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --panel: #ffffff;
            --primary: #0f766e;
            --primary-soft: #ccfbf1;
            --danger: #b91c1c;
            --warning: #a16207;
            --ok: #166534;
            --muted: #6b7280;
            --line: #e5e7eb;
            --text: #111827;
            --nav: #111827;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top left, #d1fae5 0%, var(--bg) 35%);
        }
        a { color: inherit; text-decoration: none; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 24px 18px;
        }
        .brand { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .sub-brand { color: #cbd5e1; font-size: 12px; margin-bottom: 24px; }
        .menu a {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            color: #dbeafe;
            font-size: 14px;
        }
        .menu a:hover { background: rgba(255, 255, 255, 0.12); }
        .content { flex: 1; padding: 24px; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .topbar h1 { margin: 0; font-size: 24px; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }
        .muted { color: var(--muted); font-size: 13px; }
        .btn {
            display: inline-block;
            border: 0;
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn.secondary { background: #334155; }
        .btn.warn { background: #b45309; }
        .btn.danger { background: var(--danger); }
        .btn.ghost {
            background: #fff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        .row { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
        .field { display: flex; flex-direction: column; gap: 6px; min-width: 180px; }
        input, select, textarea {
            width: 100%;
            padding: 9px 10px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            background: #fff;
            font: inherit;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        .tag {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: #115e59;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge { background: #e2e8f0; color: #0f172a; }
        .alert {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .errors { color: #b91c1c; font-size: 13px; margin: 0; padding-left: 18px; }
        .spaced { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mt-16 { margin-top: 16px; }
        .mb-0 { margin-bottom: 0; }

        @media (max-width: 960px) {
            .wrapper { flex-direction: column; }
            .sidebar { width: 100%; }
            .content { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    @auth
        <aside class="sidebar">
            <div class="brand">Nale Laundry</div>
            <div class="sub-brand">Sistem Operasional</div>
            <nav class="menu">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a href="{{ route('admin.laundry-orders.index') }}">Order Laundry</a>
                <a href="{{ route('admin.customers.index') }}">Pelanggan</a>
                <a href="{{ route('admin.service-packages.index') }}">Paket Layanan</a>
                <a href="{{ route('admin.inventory-items.index') }}">Inventory</a>
                <a href="{{ route('admin.stock-movements.index') }}">Mutasi Stok</a>
                <a href="{{ route('admin.stock-opnames.index') }}">Stok Opname</a>
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mt-16">
                @csrf
                <button class="btn secondary" type="submit">Logout</button>
            </form>
        </aside>
    @endauth

    <main class="content">
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <ul class="errors">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        {{ $slot }}
    </main>
</div>
</body>
</html>
