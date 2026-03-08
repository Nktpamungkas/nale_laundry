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
        .sidebar-head { display: block; }
        .brand { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .sub-brand { color: #cbd5e1; font-size: 12px; margin-bottom: 24px; }
        .menu-toggle {
            display: none;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(15, 23, 42, 0.4);
            color: #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 0;
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            min-height: 40px;
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
        .alert.warning { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .errors { color: #b91c1c; font-size: 13px; margin: 0; padding-left: 18px; }
        .spaced { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mt-16 { margin-top: 16px; }
        .mb-0 { margin-bottom: 0; }

        @media (max-width: 960px) {
            .wrapper { flex-direction: column; }
            .sidebar {
                width: 100%;
                padding: 12px 14px;
                position: sticky;
                top: 0;
                z-index: 20;
                border-bottom: 1px solid rgba(148, 163, 184, 0.3);
            }
            .sidebar-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }
            .brand { margin-bottom: 0; font-size: 18px; }
            .sub-brand { margin-bottom: 0; font-size: 11px; }
            .menu-toggle { display: inline-flex; }
            .menu, .logout-form { display: none; }
            .sidebar.open .menu, .sidebar.open .logout-form { display: block; }
            .menu { margin-top: 12px; }
            .menu a { margin-bottom: 6px; padding: 10px; }
            .content { padding: 12px; }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 12px;
            }
            .topbar h1 { font-size: 20px; }
            .panel {
                padding: 12px;
                margin-bottom: 12px;
                border-radius: 12px;
            }
            .grid { grid-template-columns: 1fr; gap: 10px; }
            .card { padding: 12px; }
            .row { gap: 8px; align-items: stretch; }
            .row > .field {
                min-width: 0 !important;
                flex: 1 1 100% !important;
            }
            .row > .btn,
            .row > a.btn,
            .row > button.btn { width: 100%; }
            .field {
                width: 100%;
                min-width: 0;
            }
            input, select, textarea { padding: 10px 11px; }
            table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }
            th, td { padding: 8px; }
            .alert {
                font-size: 13px;
                padding: 9px 10px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    @auth
        <aside class="sidebar" id="app-sidebar">
            <div class="sidebar-head">
                <div>
                    <div class="brand">Nale Laundry</div>
                    <div class="sub-brand">Sistem Operasional</div>
                </div>
                <button type="button" class="menu-toggle" id="menu-toggle" aria-expanded="false" aria-controls="main-menu">Menu</button>
            </div>
            <nav class="menu" id="main-menu">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a href="{{ route('admin.laundry-orders.index') }}">Order Laundry</a>
                <a href="{{ route('admin.scan.index') }}">Scan Station</a>
                @if(auth()->user()?->hasAnyRole(['owner', 'admin', 'kasir']))
                    <a href="{{ route('admin.customers.index') }}">Pelanggan</a>
                @endif
                @if(auth()->user()?->hasAnyRole(['owner', 'admin']))
                    <a href="{{ route('admin.service-packages.index') }}">Paket Layanan</a>
                @endif
                @if(auth()->user()?->hasAnyRole(['owner', 'admin', 'operator']))
                    <a href="{{ route('admin.inventory-items.index') }}">Inventory</a>
                    <a href="{{ route('admin.stock-movements.index') }}">Mutasi Stok</a>
                    <a href="{{ route('admin.stock-opnames.index') }}">Stok Opname</a>
                @endif
                @if(auth()->user()?->hasAnyRole(['owner', 'admin', 'kasir']))
                    <a href="{{ route('admin.reports.monthly') }}">Laporan Bulanan</a>
                @endif
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mt-16 logout-form">
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
        @if(session('warning'))
            <div class="alert warning">{{ session('warning') }}</div>
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
<script>
(function () {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.getElementById('menu-toggle');

    if (!sidebar || !toggle) {
        return;
    }

    const media = window.matchMedia('(max-width: 960px)');

    function syncMenuState() {
        if (media.matches) {
            sidebar.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            return;
        }

        sidebar.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function () {
        const isOpen = sidebar.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    sidebar.querySelectorAll('.menu a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (!media.matches) {
                return;
            }

            sidebar.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    if (typeof media.addEventListener === 'function') {
        media.addEventListener('change', syncMenuState);
    } else if (typeof media.addListener === 'function') {
        media.addListener(syncMenuState);
    }

    syncMenuState();
})();
</script>
<script>
(function () {
    function parseGroupedNumber(value, decimals) {
        let source = String(value ?? '').trim();
        if (!source) {
            return null;
        }

        source = source.replace(/\s+/g, '');

        // For money inputs (decimals = 0), always treat separators as grouping separators.
        if (decimals === 0) {
            const negative = source.startsWith('-');
            let unsigned = negative ? source.slice(1) : source;

            // Handle values coming from DB like "5000.00" / "5000,00".
            if (/^\d{1,3}(\.\d{3})+,\d+$/.test(unsigned)) {
                unsigned = unsigned.replace(/\./g, '').split(',')[0];
            } else if (/^\d{1,3}(,\d{3})+\.\d+$/.test(unsigned)) {
                unsigned = unsigned.replace(/,/g, '').split('.')[0];
            } else if (/^\d+[.,]\d+$/.test(unsigned) && !/^\d{1,3}([.,]\d{3})+$/.test(unsigned)) {
                const separator = unsigned.includes(',') ? ',' : '.';
                const parts = unsigned.split(separator);
                const fraction = parts[1] ?? '';

                if (fraction.length <= 2) {
                    unsigned = parts[0];
                }
            }

            const digits = unsigned.replace(/\D/g, '');

            if (!digits) {
                return null;
            }

            const parsedInteger = Number((negative ? '-' : '') + digits);

            return Number.isFinite(parsedInteger) ? parsedInteger : null;
        }

        const hasDot = source.includes('.');
        const hasComma = source.includes(',');

        if (hasDot && hasComma) {
            if (source.lastIndexOf(',') > source.lastIndexOf('.')) {
                source = source.replace(/\./g, '').replace(',', '.');
            } else {
                source = source.replace(/,/g, '');
            }
        } else if (hasDot) {
            if (/^\d{1,3}(\.\d{3})+$/.test(source)) {
                source = source.replace(/\./g, '');
            }
        } else if (hasComma) {
            if (/^\d{1,3}(,\d{3})+$/.test(source)) {
                source = source.replace(/,/g, '');
            } else {
                source = source.replace(',', '.');
            }
        }

        const parsed = Number(source);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function normalizeNumber(value, decimals) {
        const factor = 10 ** decimals;
        return Math.round(value * factor) / factor;
    }

    function formatInput(input) {
        const decimals = Number(input.dataset.decimals ?? '0');
        const parsed = parseGroupedNumber(input.value, decimals);

        if (parsed === null) {
            input.value = '';
            return;
        }

        const normalized = normalizeNumber(parsed, decimals);
        input.value = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(normalized);
    }

    function serializeForSubmit(input) {
        const decimals = Number(input.dataset.decimals ?? '0');
        const parsed = parseGroupedNumber(input.value, decimals);

        if (parsed === null) {
            input.value = '';
            return;
        }

        const normalized = normalizeNumber(parsed, decimals);
        input.value = decimals > 0 ? normalized.toFixed(decimals) : String(Math.round(normalized));
    }

    function formatAll(context) {
        context.querySelectorAll('input[data-format="grouped-number"]').forEach(formatInput);
    }

    formatAll(document);

    document.addEventListener('input', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('input[data-format="grouped-number"]')) {
            formatInput(target);
        }
    });

    document.addEventListener('blur', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('input[data-format="grouped-number"]')) {
            formatInput(target);
        }
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.querySelectorAll('input[data-format="grouped-number"]').forEach(serializeForSubmit);
    }, true);
})();
</script>
</body>
</html>
