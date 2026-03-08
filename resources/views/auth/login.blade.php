<x-layouts.app :title="'Login Admin - Nale Laundry'">
    <div class="topbar">
        <h1>Login Admin</h1>
    </div>
    <div class="panel" style="max-width: 430px;">
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="field mt-12">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="row mt-12">
                <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            </div>
            <button class="btn mt-12" type="submit">Masuk</button>
        </form>
        <p class="muted mt-12 mb-0">Akun seeder: owner@nale-laundry.test / kasir@nale-laundry.test / operator@nale-laundry.test (password: password)</p>
        <p class="muted mt-8 mb-0"><a href="{{ route('tracking.index') }}">Ke halaman cek status pelanggan</a></p>
    </div>
</x-layouts.app>
