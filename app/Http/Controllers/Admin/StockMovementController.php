<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(): View
    {
        $stockMovements = StockMovement::query()
            ->with(['inventoryItem', 'creator'])
            ->latest('movement_date')
            ->paginate(15);

        return view('admin.stock-movements.index', compact('stockMovements'));
    }

    public function create(): View
    {
        $inventoryItems = InventoryItem::query()->orderBy('name')->get();

        return view('admin.stock-movements.create', compact('inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'movement_date' => ['required', 'date'],
            'movement_type' => ['required', Rule::in(['opening', 'purchase', 'adjustment_in', 'adjustment_out'])],
            'funding_source' => ['nullable', Rule::in(['kas_toko', 'dana_owner', 'hutang_supplier']), 'required_if:movement_type,purchase'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventoryItem = InventoryItem::query()->findOrFail($data['inventory_item_id']);
        $unitCost = (float) ($data['unit_cost'] ?? $inventoryItem->average_cost);

        $isIncoming = in_array($data['movement_type'], ['opening', 'purchase', 'adjustment_in'], true);

        $this->inventoryService->recordMovement([
            'inventory_item_id' => $data['inventory_item_id'],
            'movement_date' => $data['movement_date'],
            'movement_type' => $data['movement_type'],
            'funding_source' => $data['movement_type'] === 'purchase' ? ($data['funding_source'] ?? 'kas_toko') : null,
            'reference_type' => 'manual',
            'reference_id' => null,
            'quantity_in' => $isIncoming ? (float) $data['quantity'] : 0,
            'quantity_out' => $isIncoming ? 0 : (float) $data['quantity'],
            'unit_cost' => $unitCost,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.stock-movements.index')->with('success', 'Mutasi stok berhasil dicatat.');
    }

    public function show(string $id): RedirectResponse
    {
        return redirect()->route('admin.stock-movements.index');
    }

    public function edit(string $id): RedirectResponse
    {
        return redirect()->route('admin.stock-movements.index');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('admin.stock-movements.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        return back()->with('error', 'Mutasi stok yang sudah dicatat tidak bisa dihapus.');
    }
}
