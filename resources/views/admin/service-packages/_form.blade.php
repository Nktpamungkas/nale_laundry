@csrf
@php
    $salePriceValue = old('sale_price');
    if ($salePriceValue === null) {
        $salePriceValue = isset($servicePackage) ? (string) ((int) round((float) $servicePackage->sale_price)) : '0';
    }

    $laborCostValue = old('labor_cost');
    if ($laborCostValue === null) {
        $laborCostValue = isset($servicePackage) ? (string) ((int) round((float) $servicePackage->labor_cost)) : '0';
    }

    $overheadCostValue = old('overhead_cost');
    if ($overheadCostValue === null) {
        $overheadCostValue = isset($servicePackage) ? (string) ((int) round((float) $servicePackage->overhead_cost)) : '0';
    }
@endphp
<div class="field">
    <label>Kode Paket</label>
    <input type="text" name="code" value="{{ old('code', $servicePackage->code ?? '') }}" required>
</div>
<div class="field mt-12">
    <label>Nama Paket</label>
    <input type="text" name="name" value="{{ old('name', $servicePackage->name ?? '') }}" required>
</div>
<div class="row mt-12">
    <div class="field" style="flex: 1;">
        <label>Satuan Harga</label>
        <select name="pricing_unit" required>
            <option value="kg" @selected(old('pricing_unit', $servicePackage->pricing_unit ?? 'kg') === 'kg')>Per Kg</option>
            <option value="piece" @selected(old('pricing_unit', $servicePackage->pricing_unit ?? 'kg') === 'piece')>Per Item</option>
        </select>
    </div>
    <div class="field" style="flex: 1;">
        <label>Harga Jual</label>
        <input type="text" inputmode="numeric" name="sale_price" data-format="grouped-number" data-decimals="0" value="{{ $salePriceValue }}" required>
    </div>
</div>
<div class="row mt-12">
    <div class="field" style="flex: 1;">
        <label>Biaya Tenaga Kerja (Estimasi HPP)</label>
        <input type="text" inputmode="numeric" name="labor_cost" data-format="grouped-number" data-decimals="0" value="{{ $laborCostValue }}" required>
    </div>
    <div class="field" style="flex: 1;">
        <label>Biaya Overhead (Estimasi HPP)</label>
        <input type="text" inputmode="numeric" name="overhead_cost" data-format="grouped-number" data-decimals="0" value="{{ $overheadCostValue }}" required>
    </div>
    <div class="field" style="flex: 1;">
        <label>Estimasi Jam</label>
        <input type="number" step="0.01" min="0" name="estimated_hours" value="{{ old('estimated_hours', $servicePackage->estimated_hours ?? 0) }}">
    </div>
</div>
<div class="field mt-12">
    <label>Deskripsi</label>
    <textarea name="description" rows="3">{{ old('description', $servicePackage->description ?? '') }}</textarea>
</div>
<div class="field mt-12">
    <input type="hidden" name="is_active" value="0">
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $servicePackage->is_active ?? true))> Aktif</label>
</div>

@php
    $defaultRows = old('materials');
    if (!$defaultRows) {
        $defaultRows = isset($servicePackage)
            ? $servicePackage->materials->map(fn($m) => [
                'inventory_item_id' => $m->inventory_item_id,
                'quantity_per_unit' => $m->quantity_per_unit,
                'waste_percent' => $m->waste_percent,
            ])->toArray()
            : [
                ['inventory_item_id' => '', 'quantity_per_unit' => '', 'waste_percent' => '0'],
                ['inventory_item_id' => '', 'quantity_per_unit' => '', 'waste_percent' => '0'],
                ['inventory_item_id' => '', 'quantity_per_unit' => '', 'waste_percent' => '0'],
            ];
    }
@endphp

<div class="panel mt-16" style="background: #f8fafc; margin-bottom: 0;">
    <div class="spaced">
        <h3 class="mb-0">Resep Material (untuk HPP)</h3>
        <button type="button" class="btn ghost" onclick="addMaterialRow()">+ Tambah Baris</button>
    </div>
    <table class="mt-12" id="material-table">
        <thead>
            <tr>
                <th>Item Inventory</th>
                <th>Qty per Satuan</th>
                <th>Waste %</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($defaultRows as $idx => $row)
            <tr>
                <td>
                    <select name="materials[{{ $idx }}][inventory_item_id]">
                        <option value="">- pilih item -</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}" @selected((string)($row['inventory_item_id'] ?? '') === (string)$item->id)>{{ $item->name }} ({{ $item->unit }})</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.0001" min="0" name="materials[{{ $idx }}][quantity_per_unit]" value="{{ $row['quantity_per_unit'] ?? '' }}"></td>
                <td><input type="number" step="0.01" min="0" name="materials[{{ $idx }}][waste_percent]" value="{{ $row['waste_percent'] ?? 0 }}"></td>
                <td><button class="btn danger" type="button" onclick="removeRow(this)">x</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="row mt-12">
    <button class="btn" type="submit">Simpan</button>
    <a class="btn ghost" href="{{ route('admin.service-packages.index') }}">Batal</a>
</div>

<script>
function addMaterialRow() {
    const tbody = document.querySelector('#material-table tbody');
    const idx = tbody.querySelectorAll('tr').length;
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select name="materials[${idx}][inventory_item_id]">
                <option value="">- pilih item -</option>
                @foreach($inventoryItems as $item)
                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.0001" min="0" name="materials[${idx}][quantity_per_unit]"></td>
        <td><input type="number" step="0.01" min="0" name="materials[${idx}][waste_percent]" value="0"></td>
        <td><button class="btn danger" type="button" onclick="removeRow(this)">x</button></td>`;
    tbody.appendChild(row);
}
function removeRow(button) {
    button.closest('tr').remove();
}
</script>
