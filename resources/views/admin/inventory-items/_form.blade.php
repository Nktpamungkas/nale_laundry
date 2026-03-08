@csrf
@php
    $minimumStockValue = old('minimum_stock');
    if ($minimumStockValue === null) {
        $rawMinimum = isset($inventoryItem) ? (float) $inventoryItem->minimum_stock : 0;
        $minimumStockValue = rtrim(rtrim(number_format($rawMinimum, 3, '.', ''), '0'), '.');
        if ($minimumStockValue === '') {
            $minimumStockValue = '0';
        }
    }

    $currentStockValue = old('current_stock');
    if ($currentStockValue === null) {
        $rawCurrent = isset($inventoryItem) ? (float) $inventoryItem->current_stock : 0;
        $currentStockValue = rtrim(rtrim(number_format($rawCurrent, 3, '.', ''), '0'), '.');
        if ($currentStockValue === '') {
            $currentStockValue = '0';
        }
    }

    $averageCostValue = old('average_cost');
    if ($averageCostValue === null) {
        $averageCostValue = isset($inventoryItem) ? (string) ((int) round((float) $inventoryItem->average_cost)) : '0';
    }

    $lastPurchaseCostValue = old('last_purchase_cost');
    if ($lastPurchaseCostValue === null) {
        $lastPurchaseCostValue = isset($inventoryItem) ? (string) ((int) round((float) $inventoryItem->last_purchase_cost)) : '0';
    }
@endphp
<div class="row">
    <div class="field" style="flex: 1;">
        <label>SKU</label>
        <input type="text" name="sku" value="{{ old('sku', $inventoryItem->sku ?? '') }}" required>
    </div>
    <div class="field" style="flex: 2;">
        <label>Nama Item</label>
        <input type="text" name="name" value="{{ old('name', $inventoryItem->name ?? '') }}" required>
    </div>
</div>
<div class="row mt-12">
    <div class="field" style="flex: 1;">
        <label>Kategori</label>
        <input type="text" name="category" value="{{ old('category', $inventoryItem->category ?? '') }}">
    </div>
    <div class="field" style="flex: 1;">
        <label>Satuan</label>
        <input type="text" name="unit" value="{{ old('unit', $inventoryItem->unit ?? '') }}" placeholder="liter, kg, pcs" required>
    </div>
</div>
<div class="row mt-12">
    <div class="field" style="flex: 1;">
        <label>Minimum Stok</label>
        <input type="number" step="0.001" min="0" name="minimum_stock" value="{{ $minimumStockValue }}" required>
    </div>
    <div class="field" style="flex: 1;">
        <label>Stok Saat Ini</label>
        <input type="number" step="0.001" min="0" name="current_stock" value="{{ $currentStockValue }}" required>
    </div>
</div>
<div class="row mt-12">
    <div class="field" style="flex: 1;">
        <label>Harga Rata-Rata</label>
        <input type="text" inputmode="numeric" name="average_cost" data-format="grouped-number" data-decimals="0" value="{{ $averageCostValue }}" required>
    </div>
    <div class="field" style="flex: 1;">
        <label>Harga Beli Terakhir</label>
        <input type="text" inputmode="numeric" name="last_purchase_cost" data-format="grouped-number" data-decimals="0" value="{{ $lastPurchaseCostValue }}" required>
    </div>
</div>
<div class="field mt-12">
    <input type="hidden" name="is_active" value="0">
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $inventoryItem->is_active ?? true))> Aktif</label>
</div>
<div class="row mt-12">
    <button class="btn" type="submit">Simpan</button>
    <a href="{{ route('admin.inventory-items.index') }}" class="btn ghost">Batal</a>
</div>
