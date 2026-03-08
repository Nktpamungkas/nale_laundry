<x-layouts.app :title="'Edit Paket Layanan'">
    <div class="topbar"><h1>Edit Paket Layanan</h1></div>
    <div class="panel">
        <form action="{{ route('admin.service-packages.update', $servicePackage) }}" method="POST">
            @method('PUT')
            @include('admin.service-packages._form')
        </form>
    </div>
</x-layouts.app>
