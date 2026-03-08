@csrf
<div class="field">
    <label>Nama Pelanggan</label>
    <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
</div>
<div class="field mt-12">
    <label>No. HP</label>
    <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" required>
</div>
<div class="field mt-12">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
</div>
<div class="field mt-12">
    <label>Alamat</label>
    <textarea name="address" rows="3">{{ old('address', $customer->address ?? '') }}</textarea>
</div>
<div class="field mt-12">
    <label>Catatan</label>
    <textarea name="notes" rows="3">{{ old('notes', $customer->notes ?? '') }}</textarea>
</div>
<div class="row mt-12">
    <button class="btn" type="submit">Simpan</button>
    <a class="btn ghost" href="{{ route('admin.customers.index') }}">Batal</a>
</div>
