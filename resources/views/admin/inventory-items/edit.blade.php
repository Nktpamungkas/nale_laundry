<x-layouts.app :title="'Edit Inventory Item'">
    <div class="topbar"><h1>Edit Inventory Item</h1></div>
    <div class="panel" style="max-width: 860px;">
        <form action="{{ route('admin.inventory-items.update', $inventoryItem) }}" method="POST">
            @method('PUT')
            @include('admin.inventory-items._form')
        </form>
    </div>
</x-layouts.app>
