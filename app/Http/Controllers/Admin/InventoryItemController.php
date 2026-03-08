<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Request $request): View
    {
        $lowOnly = $request->boolean('low');

        $inventoryItems = InventoryItem::query()
            ->when($lowOnly, function ($query) {
                $query->whereColumn('current_stock', '<=', 'minimum_stock');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.inventory-items.index', compact('inventoryItems', 'lowOnly'));
    }

    public function create(): View
    {
        return view('admin.inventory-items.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        InventoryItem::query()->create($data);

        return redirect()->route('admin.inventory-items.index')->with('success', 'Item inventory berhasil ditambahkan.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $inventoryItem->load(['stockMovements' => fn ($query) => $query->latest()->limit(20)]);

        return view('admin.inventory-items.show', compact('inventoryItem'));
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        return view('admin.inventory-items.edit', compact('inventoryItem'));
    }

    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $data = $this->validatePayload($request, $inventoryItem->id);
        $inventoryItem->update($data);

        return redirect()->route('admin.inventory-items.index')->with('success', 'Item inventory berhasil diperbarui.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        if ($inventoryItem->stockMovements()->exists() || $inventoryItem->servicePackageMaterials()->exists()) {
            return back()->with('error', 'Item tidak bisa dihapus karena sudah dipakai di pergerakan stok atau resep layanan.');
        }

        $inventoryItem->delete();

        return redirect()->route('admin.inventory-items.index')->with('success', 'Item inventory berhasil dihapus.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:40', Rule::unique('inventory_items', 'sku')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'average_cost' => ['required', 'numeric', 'min:0'],
            'last_purchase_cost' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
