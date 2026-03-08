<x-layouts.app :title="'Edit Header Order'">
    <div class="topbar"><h1>Edit Header Order {{ $laundryOrder->order_number }}</h1></div>

    <div class="panel" style="max-width: 760px;">
        <form action="{{ route('admin.laundry-orders.update', $laundryOrder) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="field">
                <label>Estimasi Selesai</label>
                <input type="datetime-local" name="due_at" value="{{ old('due_at', optional($laundryOrder->due_at)->format('Y-m-d\\TH:i')) }}">
            </div>
            <div class="row mt-12">
                <div class="field" style="flex:1;">
                    <label>Diskon</label>
                    @php
                        $discountValue = old('discount_amount');
                        if ($discountValue === null) {
                            $discountValue = (string) ((int) round((float) $laundryOrder->discount_amount));
                        }
                    @endphp
                    <input type="text" inputmode="numeric" name="discount_amount" data-format="grouped-number" data-decimals="0" value="{{ $discountValue }}" required>
                </div>
            </div>
            <div class="field mt-12">
                <label>Catatan</label>
                <textarea name="status_note" rows="3">{{ old('status_note', $laundryOrder->status_note) }}</textarea>
            </div>
            <div class="row mt-12">
                <button class="btn" type="submit">Simpan</button>
                <a class="btn ghost" href="{{ route('admin.laundry-orders.show', $laundryOrder) }}">Kembali</a>
            </div>
        </form>
    </div>
</x-layouts.app>
