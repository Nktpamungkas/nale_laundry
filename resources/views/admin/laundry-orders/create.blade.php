<x-layouts.app :title="'Buat Order Laundry'">
    <div class="topbar"><h1>Buat Order Laundry</h1></div>

    @php
        $customerMode = old('customer_mode', 'existing');
    @endphp

    <form action="{{ route('admin.laundry-orders.store') }}" method="POST" class="panel">
        @csrf
        <div class="panel" style="background:#f8fafc;">
            <div class="row">
                <div class="field" style="flex:1;">
                    <label>Mode Pelanggan</label>
                    <div class="row" style="gap:12px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="customer_mode" value="existing" @checked($customerMode === 'existing')> Pilih yang sudah ada
                        </label>
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="customer_mode" value="new" @checked($customerMode === 'new')> Input pelanggan baru
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mt-12">
                <div class="field" id="existing-customer-field" style="flex:1;">
                    <label>Pelanggan</label>
                    <input type="text" id="customer-search" placeholder="Cari nama / no HP / kode pelanggan">
                    <select name="customer_id" id="customer-id-select">
                        <option value="">- pilih pelanggan -</option>
                        @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                data-search="{{ strtolower($customer->name.' '.$customer->phone.' '.$customer->code) }}"
                                data-phone="{{ $customer->phone }}"
                                data-name="{{ $customer->name }}"
                                data-code="{{ $customer->code }}"
                                @selected(old('customer_id') == $customer->id)
                            >
                                {{ $customer->name }} ({{ $customer->phone }}) - {{ $customer->code }}
                            </option>
                        @endforeach
                    </select>
                    <p class="muted mb-0 mt-8" id="customer-search-info">Total pelanggan: {{ $customers->count() }}</p>
                </div>
                <div class="field new-customer-field" style="flex:1;">
                    <label>Nama Pelanggan Baru</label>
                    <input type="text" name="new_customer_name" value="{{ old('new_customer_name') }}" placeholder="Contoh: Budi">
                </div>
                <div class="field new-customer-field" style="flex:1;">
                    <label>No. HP Pelanggan Baru</label>
                    <input type="text" name="new_customer_phone" value="{{ old('new_customer_phone') }}" placeholder="Contoh: 0812xxxx">
                    <p id="new-customer-phone-alert" class="mb-0 mt-8" style="display:none; color:#991b1b; font-size:13px; font-weight:600;"></p>
                </div>
                <div class="field new-customer-field" style="flex:1;">
                    <label>Email (opsional)</label>
                    <input type="email" name="new_customer_email" value="{{ old('new_customer_email') }}" placeholder="email@domain.com">
                </div>
            </div>

            <p class="muted mb-0 mt-8">Nomor WhatsApp harus unik. Jika nomor sudah ada, pilih dari daftar pelanggan yang sudah terdaftar.</p>
        </div>

        <div class="row">
            <div class="field" style="flex: 1;">
                <label>Tanggal Terima</label>
                <input type="datetime-local" name="received_at" value="{{ old('received_at', now()->format('Y-m-d\\TH:i')) }}" required>
            </div>
            <div class="field" style="flex: 1;">
                <label>Estimasi Selesai</label>
                <input type="datetime-local" name="due_at" value="{{ old('due_at') }}">
            </div>
        </div>

        <div class="row mt-12">
            <div class="field" style="flex: 1;">
                <label>Diskon</label>
                <input type="text" inputmode="numeric" name="discount_amount" data-format="grouped-number" data-decimals="0" value="{{ old('discount_amount', 0) }}">
            </div>
            <div class="field" style="flex: 3;">
                <label>Catatan</label>
                <input type="text" name="status_note" value="{{ old('status_note') }}" placeholder="Catatan tambahan order">
            </div>
        </div>

        @php
            $lines = old('items', [
                ['service_package_id' => '', 'quantity' => '', 'unit_price' => '', 'description' => ''],
                ['service_package_id' => '', 'quantity' => '', 'unit_price' => '', 'description' => ''],
            ]);

            $packageStockMeta = $servicePackages->mapWithKeys(function ($package) {
                $materials = $package->materials->map(function ($material) {
                    $inventory = $material->inventoryItem;

                    return [
                        'inventory_item_id' => (int) $material->inventory_item_id,
                        'per_unit' => round((float) $material->quantity_per_unit * (1 + ((float) $material->waste_percent / 100)), 6),
                        'item_name' => $inventory?->name ?? ('Item #'.$material->inventory_item_id),
                        'unit' => $inventory?->unit ?? '',
                    ];
                })->values()->all();

                return [
                    (string) $package->id => [
                        'name' => $package->name,
                        'pricing_unit' => $package->pricing_unit,
                        'materials' => $materials,
                    ],
                ];
            })->all();

            $inventoryStocks = [];
            foreach ($servicePackages as $package) {
                foreach ($package->materials as $material) {
                    if (! $material->inventoryItem) {
                        continue;
                    }

                    $inventoryStocks[(string) $material->inventory_item_id] = [
                        'stock' => (float) $material->inventoryItem->current_stock,
                        'name' => $material->inventoryItem->name,
                        'unit' => $material->inventoryItem->unit,
                    ];
                }
            }
        @endphp

        <div class="panel mt-16" style="background: #f8fafc; margin-bottom: 0;">
            <div class="spaced">
                <h3 class="mb-0">Item Layanan</h3>
                <button type="button" class="btn ghost" onclick="addLine()">+ Tambah Baris</button>
            </div>
            <table class="mt-12" id="order-line-table">
                <thead>
                    <tr>
                        <th>Paket Layanan</th>
                        <th>Qty</th>
                        <th>Unit Price (otomatis, bisa diubah)</th>
                        <th>Deskripsi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lines as $idx => $line)
                    <tr>
                        <td>
                            <select name="items[{{ $idx }}][service_package_id]" required>
                                <option value="">- pilih paket -</option>
                                @foreach($servicePackages as $package)
                                    <option value="{{ $package->id }}" data-sale-price="{{ (float) $package->sale_price }}" @selected((string)($line['service_package_id'] ?? '') === (string)$package->id)>{{ $package->name }} ({{ $package->pricing_unit }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.001" min="0" name="items[{{ $idx }}][quantity]" value="{{ $line['quantity'] ?? '' }}" required>
                            <div class="muted mt-8 js-quota-hint"></div>
                        </td>
                        <td><input type="text" inputmode="numeric" name="items[{{ $idx }}][unit_price]" data-format="grouped-number" data-decimals="0" value="{{ $line['unit_price'] ?? '' }}"></td>
                        <td><input type="text" name="items[{{ $idx }}][description]" value="{{ $line['description'] ?? '' }}"></td>
                        <td><button type="button" class="btn danger" onclick="removeRow(this)">x</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-12">
            <button class="btn" type="submit">Simpan Order</button>
            <a class="btn ghost" href="{{ route('admin.laundry-orders.index') }}">Batal</a>
        </div>
    </form>

<script>
function addLine() {
    const tbody = document.querySelector('#order-line-table tbody');
    const idx = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][service_package_id]" required>
                <option value="">- pilih paket -</option>
                @foreach($servicePackages as $package)
                    <option value="{{ $package->id }}" data-sale-price="{{ (float) $package->sale_price }}">{{ $package->name }} ({{ $package->pricing_unit }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.001" min="0" name="items[${idx}][quantity]" required>
            <div class="muted mt-8 js-quota-hint"></div>
        </td>
        <td><input type="text" inputmode="numeric" name="items[${idx}][unit_price]" data-format="grouped-number" data-decimals="0"></td>
        <td><input type="text" name="items[${idx}][description]"></td>
        <td><button type="button" class="btn danger" onclick="removeRow(this)">x</button></td>`;
    tbody.appendChild(tr);
}
function removeRow(button) {
    button.closest('tr').remove();
}
</script>
<script>
(function () {
    const form = document.querySelector('form[action="{{ route('admin.laundry-orders.store') }}"]');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const orderLineTable = document.getElementById('order-line-table');
    const modeInputs = document.querySelectorAll('input[name="customer_mode"]');
    const existingField = document.getElementById('existing-customer-field');
    const existingSelect = document.querySelector('select[name="customer_id"]');
    const existingSearch = document.getElementById('customer-search');
    const existingSearchInfo = document.getElementById('customer-search-info');
    const newFields = document.querySelectorAll('.new-customer-field');
    const newName = document.querySelector('input[name="new_customer_name"]');
    const newPhone = document.querySelector('input[name="new_customer_phone"]');
    const newPhoneAlert = document.getElementById('new-customer-phone-alert');
    const selectOptions = Array.from(existingSelect.options).filter((option) => option.value !== '');
    const customersByPhone = new Map();
    const packageStockMeta = @json($packageStockMeta);
    const inventoryStocks = @json($inventoryStocks);
    let hasDuplicatePhone = false;
    let hasStockIssue = false;

    function normalizePhone(value) {
        return (value || '').replace(/\D+/g, '');
    }

    selectOptions.forEach((option) => {
        const normalized = normalizePhone(option.dataset.phone || '');
        if (!normalized) {
            return;
        }

        customersByPhone.set(normalized, {
            name: option.dataset.name || option.textContent || '',
            code: option.dataset.code || '',
        });
    });

    function filterExistingCustomers() {
        const query = (existingSearch.value || '').trim().toLowerCase();
        let visibleCount = 0;

        selectOptions.forEach((option) => {
            const haystack = (option.dataset.search || option.textContent || '').toLowerCase();
            const visible = !query || haystack.includes(query);
            option.hidden = !visible;

            if (visible) {
                visibleCount += 1;
            }
        });

        if (query && existingSelect.selectedOptions[0] && existingSelect.selectedOptions[0].hidden) {
            existingSelect.value = '';
        }

        if (!query) {
            existingSearchInfo.textContent = 'Total pelanggan: ' + selectOptions.length;
            return;
        }

        existingSearchInfo.textContent = 'Hasil ditemukan: ' + visibleCount;
    }

    function toRupiahText(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) {
            return '';
        }

        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(Math.round(numeric));
    }

    function applyDefaultUnitPrice(row, force = false) {
        const packageSelect = row.querySelector('select[name*="[service_package_id]"]');
        const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');

        if (!packageSelect || !unitPriceInput) {
            return;
        }

        const selectedOption = packageSelect.options[packageSelect.selectedIndex];
        const salePrice = selectedOption?.dataset?.salePrice;

        if (!salePrice) {
            if (force) {
                unitPriceInput.value = '';
                unitPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return;
        }

        if (!force && (unitPriceInput.value || '').trim() !== '') {
            return;
        }

        unitPriceInput.value = toRupiahText(salePrice);
        unitPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function parseQtyInput(value) {
        const source = String(value ?? '').trim().replace(',', '.');
        const parsed = Number(source);

        if (!Number.isFinite(parsed) || parsed < 0) {
            return 0;
        }

        return parsed;
    }

    function formatQtyLabel(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) {
            return '0';
        }

        const rounded = Math.floor(Math.max(numeric, 0) * 1000) / 1000;
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        }).format(rounded);
    }

    function lineRows() {
        if (!orderLineTable) {
            return [];
        }

        return Array.from(orderLineTable.querySelectorAll('tbody tr'));
    }

    function syncSubmitState() {
        if (!submitBtn) {
            return;
        }

        submitBtn.disabled = hasDuplicatePhone || hasStockIssue;
    }

    function allocatedUsageExcluding(excludeRow) {
        const usage = {};

        lineRows().forEach((row) => {
            if (row === excludeRow) {
                return;
            }

            const packageSelect = row.querySelector('select[name*="[service_package_id]"]');
            const qtyInput = row.querySelector('input[name*="[quantity]"]');
            if (!packageSelect || !qtyInput) {
                return;
            }

            const pkg = packageStockMeta[String(packageSelect.value || '')];
            const qty = parseQtyInput(qtyInput.value);

            if (!pkg || !Array.isArray(pkg.materials) || qty <= 0) {
                return;
            }

            pkg.materials.forEach((material) => {
                const perUnit = Number(material.per_unit ?? 0);
                if (!(perUnit > 0)) {
                    return;
                }

                const itemKey = String(material.inventory_item_id);
                usage[itemKey] = (usage[itemKey] ?? 0) + (qty * perUnit);
            });
        });

        return usage;
    }

    function updateRowQuotaHint(row) {
        const hint = row.querySelector('.js-quota-hint');
        const packageSelect = row.querySelector('select[name*="[service_package_id]"]');
        const qtyInput = row.querySelector('input[name*="[quantity]"]');

        if (!hint || !packageSelect || !qtyInput) {
            return true;
        }

        const pkg = packageStockMeta[String(packageSelect.value || '')];
        if (!pkg || !Array.isArray(pkg.materials) || pkg.materials.length === 0) {
            hint.textContent = '';
            hint.style.color = '';
            row.dataset.stockInvalid = '0';
            return true;
        }

        const requestedQty = parseQtyInput(qtyInput.value);
        const usageByOthers = allocatedUsageExcluding(row);
        let maxQty = Number.POSITIVE_INFINITY;
        const shortages = [];

        pkg.materials.forEach((material) => {
            const perUnit = Number(material.per_unit ?? 0);
            if (!(perUnit > 0)) {
                return;
            }

            const itemKey = String(material.inventory_item_id);
            const stockMeta = inventoryStocks[itemKey] || { stock: 0 };
            const available = Number(stockMeta.stock ?? 0) - Number(usageByOthers[itemKey] ?? 0);
            const allowedQty = available / perUnit;
            maxQty = Math.min(maxQty, allowedQty);

            const needed = requestedQty * perUnit;
            const shortage = needed - available;
            if (shortage > 0.000001) {
                shortages.push(
                    (material.item_name || ('Item #' + itemKey))
                    + ' kurang ' + formatQtyLabel(shortage) + ' ' + (material.unit || '')
                );
            }
        });

        if (!Number.isFinite(maxQty)) {
            maxQty = 0;
        }

        maxQty = Math.max(0, Math.floor(maxQty * 1000) / 1000);
        const remaining = Math.max(maxQty - requestedQty, 0);
        const unitLabel = pkg.pricing_unit === 'piece' ? 'item' : 'kg';

        if (requestedQty > maxQty + 0.000001) {
            hint.style.color = '#991b1b';
            const shortageText = shortages.length > 0 ? ' Kekurangan: ' + shortages.join('; ') + '.' : '';
            hint.textContent = 'Stok tidak cukup. Maksimal ' + formatQtyLabel(maxQty) + ' ' + unitLabel + '.' + shortageText;
            row.dataset.stockInvalid = '1';
            return false;
        }

        hint.style.color = '';
        hint.textContent = 'Sisa kuota saat ini: ' + formatQtyLabel(remaining) + ' ' + unitLabel
            + ' (maksimal ' + formatQtyLabel(maxQty) + ' ' + unitLabel + ').';
        row.dataset.stockInvalid = '0';
        return true;
    }

    function syncQuotaHints() {
        if (!orderLineTable) {
            hasStockIssue = false;
            syncSubmitState();
            return;
        }

        hasStockIssue = false;
        lineRows().forEach((row) => {
            const rowValid = updateRowQuotaHint(row);
            if (!rowValid) {
                hasStockIssue = true;
            }
        });

        syncSubmitState();
    }

    function syncAllUnitPricesOnLoad() {
        if (!orderLineTable) {
            return;
        }

        orderLineTable.querySelectorAll('tbody tr').forEach((row) => {
            applyDefaultUnitPrice(row, false);
        });
    }

    function checkDuplicatePhone() {
        const selected = document.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
        if (selected !== 'new') {
            newPhone.setCustomValidity('');
            newPhoneAlert.style.display = 'none';
            hasDuplicatePhone = false;
            syncSubmitState();
            return;
        }

        const normalized = normalizePhone(newPhone.value);
        const duplicate = normalized ? customersByPhone.get(normalized) : null;

        if (!duplicate) {
            newPhone.setCustomValidity('');
            newPhoneAlert.style.display = 'none';
            hasDuplicatePhone = false;
            syncSubmitState();
            return;
        }

        const detail = duplicate.code ? ' (' + duplicate.code + ')' : '';
        newPhoneAlert.textContent = 'Nomor sudah terdaftar atas nama ' + duplicate.name + detail + '.';
        newPhoneAlert.style.display = 'block';
        newPhone.setCustomValidity('Nomor WhatsApp sudah terdaftar.');
        hasDuplicatePhone = true;
        syncSubmitState();
    }

    function syncCustomerMode() {
        const selected = document.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
        const isNew = selected === 'new';

        existingField.style.display = isNew ? 'none' : 'flex';
        existingSelect.required = !isNew;
        existingSearch.required = false;

        newFields.forEach((field) => {
            field.style.display = isNew ? 'flex' : 'none';
        });
        newName.required = isNew;
        newPhone.required = isNew;
        checkDuplicatePhone();
    }

    modeInputs.forEach((input) => {
        input.addEventListener('change', syncCustomerMode);
    });

    existingSearch.addEventListener('input', filterExistingCustomers);
    newPhone.addEventListener('input', checkDuplicatePhone);
    newPhone.addEventListener('blur', checkDuplicatePhone);

    if (orderLineTable) {
        const tbody = orderLineTable.querySelector('tbody');
        if (tbody) {
            const observer = new MutationObserver(function () {
                syncAllUnitPricesOnLoad();
                syncQuotaHints();
            });
            observer.observe(tbody, { childList: true });
        }

        orderLineTable.addEventListener('change', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) && !(target instanceof HTMLSelectElement)) {
                return;
            }

            if (target instanceof HTMLSelectElement && target.name.includes('[service_package_id]')) {
                const row = target.closest('tr');
                if (!row) {
                    return;
                }

                applyDefaultUnitPrice(row, true);
                syncQuotaHints();
                return;
            }

            if (target instanceof HTMLInputElement && target.name.includes('[quantity]')) {
                syncQuotaHints();
            }
        });

        orderLineTable.addEventListener('input', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (!target.name.includes('[quantity]')) {
                return;
            }

            syncQuotaHints();
        });
    }

    filterExistingCustomers();
    syncAllUnitPricesOnLoad();
    syncQuotaHints();
    syncCustomerMode();
})();
</script>
</x-layouts.app>
