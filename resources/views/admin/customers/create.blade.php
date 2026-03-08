<x-layouts.app :title="'Tambah Pelanggan'">
    <div class="topbar"><h1>Tambah Pelanggan</h1></div>
    <div class="panel" style="max-width: 680px;">
        <form action="{{ route('admin.customers.store') }}" method="POST">
            @include('admin.customers._form')
        </form>
    </div>
</x-layouts.app>
