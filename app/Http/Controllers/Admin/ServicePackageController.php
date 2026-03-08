<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ServicePackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    public function index(): View
    {
        $servicePackages = ServicePackage::query()
            ->withCount('materials')
            ->latest()
            ->paginate(10);

        return view('admin.service-packages.index', compact('servicePackages'));
    }

    public function create(): View
    {
        $inventoryItems = InventoryItem::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.service-packages.create', compact('inventoryItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:service_packages,code'],
            'name' => ['required', 'string', 'max:120'],
            'pricing_unit' => ['required', Rule::in(['kg', 'piece'])],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'labor_cost' => ['required', 'numeric', 'min:0'],
            'overhead_cost' => ['required', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'materials' => ['nullable', 'array'],
            'materials.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id', 'distinct'],
            'materials.*.quantity_per_unit' => ['nullable', 'numeric', 'min:0'],
            'materials.*.waste_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $servicePackage = ServicePackage::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'pricing_unit' => $data['pricing_unit'],
                'sale_price' => $data['sale_price'],
                'labor_cost' => $data['labor_cost'],
                'overhead_cost' => $data['overhead_cost'],
                'estimated_hours' => $data['estimated_hours'] ?? 0,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            foreach ($this->extractMaterialRows($data) as $row) {
                $servicePackage->materials()->create($row);
            }
        });

        return redirect()->route('admin.service-packages.index')->with('success', 'Paket layanan berhasil ditambahkan.');
    }

    public function show(ServicePackage $servicePackage): View
    {
        $servicePackage->load('materials.inventoryItem');

        return view('admin.service-packages.show', compact('servicePackage'));
    }

    public function edit(ServicePackage $servicePackage): View
    {
        $servicePackage->load('materials');
        $inventoryItems = InventoryItem::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.service-packages.edit', compact('servicePackage', 'inventoryItems'));
    }

    public function update(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('service_packages', 'code')->ignore($servicePackage->id)],
            'name' => ['required', 'string', 'max:120'],
            'pricing_unit' => ['required', Rule::in(['kg', 'piece'])],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'labor_cost' => ['required', 'numeric', 'min:0'],
            'overhead_cost' => ['required', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'materials' => ['nullable', 'array'],
            'materials.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id', 'distinct'],
            'materials.*.quantity_per_unit' => ['nullable', 'numeric', 'min:0'],
            'materials.*.waste_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($servicePackage, $data) {
            $servicePackage->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'pricing_unit' => $data['pricing_unit'],
                'sale_price' => $data['sale_price'],
                'labor_cost' => $data['labor_cost'],
                'overhead_cost' => $data['overhead_cost'],
                'estimated_hours' => $data['estimated_hours'] ?? 0,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $servicePackage->materials()->delete();
            foreach ($this->extractMaterialRows($data) as $row) {
                $servicePackage->materials()->create($row);
            }
        });

        return redirect()->route('admin.service-packages.index')->with('success', 'Paket layanan berhasil diperbarui.');
    }

    public function destroy(ServicePackage $servicePackage): RedirectResponse
    {
        if ($servicePackage->orderItems()->exists()) {
            return back()->with('error', 'Paket tidak bisa dihapus karena sudah dipakai transaksi.');
        }

        $servicePackage->delete();

        return redirect()->route('admin.service-packages.index')->with('success', 'Paket layanan berhasil dihapus.');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractMaterialRows(array $payload): array
    {
        $rows = [];

        foreach (($payload['materials'] ?? []) as $material) {
            if (empty($material['inventory_item_id']) || (float) ($material['quantity_per_unit'] ?? 0) <= 0) {
                continue;
            }

            $rows[] = [
                'inventory_item_id' => (int) $material['inventory_item_id'],
                'quantity_per_unit' => (float) $material['quantity_per_unit'],
                'waste_percent' => (float) ($material['waste_percent'] ?? 0),
            ];
        }

        return $rows;
    }
}
