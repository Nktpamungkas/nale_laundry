<x-layouts.app :title="'Edit Pelanggan'">
    <div class="topbar"><h1>Edit Pelanggan</h1></div>
    <div class="panel" style="max-width: 680px;">
        <form action="{{ route('admin.customers.update', $customer) }}" method="POST">
            @method('PUT')
            @include('admin.customers._form')
        </form>
    </div>
</x-layouts.app>
