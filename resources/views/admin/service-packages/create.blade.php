<x-layouts.app :title="'Tambah Paket Layanan'">
    <div class="topbar"><h1>Tambah Paket Layanan</h1></div>
    <div class="panel">
        <form action="{{ route('admin.service-packages.store') }}" method="POST">
            @include('admin.service-packages._form')
        </form>
    </div>
</x-layouts.app>
