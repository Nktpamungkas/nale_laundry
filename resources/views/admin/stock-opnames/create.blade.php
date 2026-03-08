<x-layouts.app :title="'Buat Draft Stok Opname'">
    <div class="topbar"><h1>Buat Draft Stok Opname</h1></div>

    <div class="panel" style="max-width: 660px;">
        <form action="{{ route('admin.stock-opnames.store') }}" method="POST">
            @csrf
            <div class="field">
                <label>Tanggal Opname</label>
                <input type="date" name="opname_date" value="{{ old('opname_date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="field mt-12">
                <label>Catatan</label>
                <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="row mt-12">
                <button type="submit" class="btn">Buat Draft</button>
                <a href="{{ route('admin.stock-opnames.index') }}" class="btn ghost">Batal</a>
            </div>
        </form>
    </div>
</x-layouts.app>
