<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockOpname;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(): View
    {
        $stockOpnames = StockOpname::query()
            ->with('creator')
            ->withCount('items')
            ->latest('opname_date')
            ->paginate(10);

        return view('admin.stock-opnames.index', compact('stockOpnames'));
    }

    public function create(): View
    {
        return view('admin.stock-opnames.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $inventoryItems = InventoryItem::query()->where('is_active', true)->orderBy('name')->get();

        if ($inventoryItems->isEmpty()) {
            return back()->with('error', 'Belum ada item inventory aktif untuk dibuatkan stok opname.');
        }

        $stockOpname = DB::transaction(function () use ($data, $request, $inventoryItems) {
            $opname = StockOpname::query()->create([
                'opname_number' => $this->nextOpnameNumber(),
                'opname_date' => $data['opname_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($inventoryItems as $item) {
                $opname->items()->create([
                    'inventory_item_id' => $item->id,
                    'system_stock' => $item->current_stock,
                    'actual_stock' => $item->current_stock,
                    'difference_stock' => 0,
                    'adjustment_cost' => 0,
                    'notes' => null,
                ]);
            }

            return $opname;
        });

        return redirect()->route('admin.stock-opnames.show', $stockOpname)->with('success', 'Draft stok opname berhasil dibuat.');
    }

    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load(['items.inventoryItem', 'creator', 'approver']);

        return view('admin.stock-opnames.show', compact('stockOpname'));
    }

    public function edit(StockOpname $stockOpname): RedirectResponse
    {
        return redirect()->route('admin.stock-opnames.show', $stockOpname);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status !== 'draft') {
            return back()->with('error', 'Stok opname yang sudah diposting tidak dapat diubah.');
        }

        $data = $request->validate([
            'actual_stock' => ['required', 'array'],
            'actual_stock.*' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string'],
            'post_after_save' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($stockOpname, $data) {
            $stockOpname->loadMissing('items.inventoryItem');

            foreach ($stockOpname->items as $item) {
                if (! array_key_exists($item->id, $data['actual_stock'])) {
                    continue;
                }

                $actual = (float) $data['actual_stock'][$item->id];
                $system = (float) $item->system_stock;
                $difference = round($actual - $system, 3);
                $cost = round($difference * (float) $item->inventoryItem->average_cost, 2);

                $item->update([
                    'actual_stock' => $actual,
                    'difference_stock' => $difference,
                    'adjustment_cost' => $cost,
                    'notes' => $data['notes'][$item->id] ?? null,
                ]);
            }
        });

        if ($request->boolean('post_after_save')) {
            return $this->post($request, $stockOpname);
        }

        return back()->with('success', 'Data stok opname berhasil disimpan.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status !== 'draft') {
            return back()->with('error', 'Stok opname yang sudah diposting tidak dapat dihapus.');
        }

        $stockOpname->delete();

        return redirect()->route('admin.stock-opnames.index')->with('success', 'Draft stok opname berhasil dihapus.');
    }

    public function post(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status !== 'draft') {
            return back()->with('error', 'Stok opname ini sudah diposting sebelumnya.');
        }

        $this->inventoryService->applyStockOpname($stockOpname, $request->user());

        return redirect()->route('admin.stock-opnames.show', $stockOpname)->with('success', 'Stok opname berhasil diposting dan stok telah disesuaikan.');
    }

    private function nextOpnameNumber(): string
    {
        $prefix = 'OPN-'.Carbon::now()->format('Ymd');

        $counter = StockOpname::query()
            ->where('opname_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
    }
}
