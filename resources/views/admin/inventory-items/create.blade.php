<x-layouts.app :title="'Tambah Inventory Item'">
    <div class="topbar"><h1>Tambah Inventory Item</h1></div>
    <div class="panel" style="max-width: 860px;">
        <form action="{{ route('admin.inventory-items.store') }}" method="POST">
            @include('admin.inventory-items._form')
        </form>
    </div>
</x-layouts.app>
