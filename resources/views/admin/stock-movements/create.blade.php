<x-layouts.app :title="'Catat Mutasi Stok'">
    @php
        $formatQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="topbar"><h1>Catat Mutasi Stok</h1></div>

    <div class="panel" style="max-width: 760px;">
        <form action="{{ route('admin.stock-movements.store') }}" method="POST">
            @csrf
            <div class="field">
                <label>Item Inventory</label>
                <select name="inventory_item_id" required>
                    <option value="">- pilih item -</option>
                    @foreach($inventoryItems as $item)
                        <option value="{{ $item->id }}" @selected(old('inventory_item_id') == $item->id)>{{ $item->name }} (stok: {{ $formatQty($item->current_stock) }} {{ $item->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-12">
                <div class="field" style="flex:1;">
                    <label>Tanggal Mutasi</label>
                    <input type="datetime-local" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d\\TH:i')) }}" required>
                </div>
                <div class="field" style="flex:1;">
                    <label>Tipe</label>
                    <select name="movement_type" required>
                        <option value="opening" @selected(old('movement_type') === 'opening')>Opening</option>
                        <option value="purchase" @selected(old('movement_type') === 'purchase')>Pembelian</option>
                        <option value="adjustment_in" @selected(old('movement_type') === 'adjustment_in')>Adjustment Masuk</option>
                        <option value="adjustment_out" @selected(old('movement_type') === 'adjustment_out')>Adjustment Keluar</option>
                    </select>
                </div>
            </div>
            <div class="row mt-12" id="funding-source-row" style="display:none;">
                <div class="field" style="flex:1;">
                    <label>Sumber Dana Pembelian</label>
                    <select name="funding_source" id="funding-source-select">
                        <option value="kas_toko" @selected(old('funding_source', 'kas_toko') === 'kas_toko')>Kas Toko</option>
                        <option value="dana_owner" @selected(old('funding_source') === 'dana_owner')>Dana Owner</option>
                        <option value="hutang_supplier" @selected(old('funding_source') === 'hutang_supplier')>Hutang Supplier</option>
                    </select>
                </div>
            </div>
            <div class="row mt-12">
                <div class="field" style="flex:1;">
                    <label>Qty</label>
                    <input type="number" step="0.001" min="0" name="quantity" value="{{ old('quantity') }}" required>
                </div>
                <div class="field" style="flex:1;">
                    <label>Biaya Satuan</label>
                    <input type="text" inputmode="numeric" name="unit_cost" data-format="grouped-number" data-decimals="0" value="{{ old('unit_cost') }}">
                </div>
            </div>
            <div class="field mt-12">
                <label>Catatan</label>
                <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>

            <div class="row mt-12">
                <button class="btn" type="submit">Simpan</button>
                <a href="{{ route('admin.stock-movements.index') }}" class="btn ghost">Batal</a>
            </div>
        </form>
    </div>
<script>
(function () {
    const movementType = document.querySelector('select[name="movement_type"]');
    const fundingRow = document.getElementById('funding-source-row');
    const fundingSelect = document.getElementById('funding-source-select');

    if (!movementType || !fundingRow || !fundingSelect) {
        return;
    }

    function syncFundingSource() {
        const isPurchase = movementType.value === 'purchase';
        fundingRow.style.display = isPurchase ? 'flex' : 'none';
        fundingSelect.required = isPurchase;
    }

    movementType.addEventListener('change', syncFundingSource);
    syncFundingSource();
})();
</script>
</x-layouts.app>
