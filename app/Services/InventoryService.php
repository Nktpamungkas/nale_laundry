<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\LaundryOrder;
use App\Models\ServicePackage;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * @param array<int, array{service_package_id:int|string, quantity:float|int|string|null}> $rawItems
     * @param Collection<int, ServicePackage> $servicePackages
     * @return array{is_available: bool, issues: array<int, array<string, mixed>>}
     */
    public function checkStockForDraftItems(array $rawItems, Collection $servicePackages, bool $lockRows = false): array
    {
        $lineItems = [];

        foreach (array_values($rawItems) as $index => $item) {
            $servicePackageId = (int) ($item['service_package_id'] ?? 0);
            $servicePackage = $servicePackages->get($servicePackageId);
            if (! $servicePackage) {
                continue;
            }

            $lineItems[] = [
                'line_no' => $index + 1,
                'quantity' => (float) ($item['quantity'] ?? 0),
                'service_package' => $servicePackage,
            ];
        }

        return $this->evaluateLineItemsAvailability($lineItems, $lockRows);
    }

    /**
     * @return array{is_available: bool, issues: array<int, array<string, mixed>>}
     */
    public function checkStockForOrder(LaundryOrder $order, bool $lockRows = false): array
    {
        $order->loadMissing('items.servicePackage.materials.inventoryItem');

        $lineItems = [];
        foreach ($order->items->values() as $index => $item) {
            if (! $item->servicePackage) {
                continue;
            }

            $lineItems[] = [
                'line_no' => $index + 1,
                'quantity' => (float) $item->quantity,
                'service_package' => $item->servicePackage,
            ];
        }

        return $this->evaluateLineItemsAvailability($lineItems, $lockRows);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function recordMovement(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            /** @var InventoryItem $item */
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($payload['inventory_item_id']);

            $qtyIn = (float) ($payload['quantity_in'] ?? 0);
            $qtyOut = (float) ($payload['quantity_out'] ?? 0);
            $unitCost = (float) ($payload['unit_cost'] ?? 0);

            $newStock = (float) $item->current_stock + $qtyIn - $qtyOut;

            if ($qtyIn > 0) {
                $oldStock = (float) $item->current_stock;
                $oldValue = $oldStock * (float) $item->average_cost;
                $inValue = $qtyIn * $unitCost;
                $combinedQty = $oldStock + $qtyIn;
                $item->average_cost = $combinedQty > 0 ? round(($oldValue + $inValue) / $combinedQty, 2) : (float) $item->average_cost;
                $item->last_purchase_cost = $unitCost;
            }

            $item->current_stock = round($newStock, 3);
            $item->save();

            $payload['movement_date'] = $payload['movement_date'] ?? Carbon::now();
            $payload['total_cost'] = round(($qtyIn > 0 ? $qtyIn : $qtyOut) * $unitCost, 2);

            return StockMovement::query()->create($payload);
        });
    }

    public function consumeMaterialsForOrder(LaundryOrder $order, ?User $actor = null): void
    {
        DB::transaction(function () use ($order, $actor) {
            $order->loadMissing('items.servicePackage.materials.inventoryItem');

            foreach ($order->items as $orderItem) {
                $qty = (float) $orderItem->quantity;

                foreach ($orderItem->servicePackage->materials as $material) {
                    $requiredQty = (float) $material->quantity_per_unit * $qty;
                    $requiredQty *= 1 + ((float) $material->waste_percent / 100);

                    if ($requiredQty <= 0) {
                        continue;
                    }

                    $this->recordMovement([
                        'inventory_item_id' => $material->inventory_item_id,
                        'movement_type' => 'usage',
                        'reference_type' => LaundryOrder::class,
                        'reference_id' => $order->id,
                        'quantity_in' => 0,
                        'quantity_out' => round($requiredQty, 3),
                        'unit_cost' => (float) $material->inventoryItem->average_cost,
                        'notes' => 'Pemakaian bahan untuk order '.$order->order_number,
                        'created_by' => $actor?->id,
                    ]);
                }
            }
        });
    }

    /**
     * @param array<int, array{line_no:int, quantity:float, service_package:ServicePackage}> $lineItems
     * @return array{is_available: bool, issues: array<int, array<string, mixed>>}
     */
    private function evaluateLineItemsAvailability(array $lineItems, bool $lockRows = false): array
    {
        $inventoryItemIds = collect($lineItems)
            ->flatMap(function (array $line) {
                /** @var ServicePackage $package */
                $package = $line['service_package'];

                return $package->materials->pluck('inventory_item_id');
            })
            ->unique()
            ->values();

        if ($inventoryItemIds->isEmpty()) {
            return ['is_available' => true, 'issues' => []];
        }

        $query = InventoryItem::query()->whereIn('id', $inventoryItemIds->all());
        if ($lockRows) {
            $query->lockForUpdate();
        }

        $stockMap = $query->get()->keyBy('id')->map(function (InventoryItem $item) {
            return (float) $item->current_stock;
        });

        $issues = [];

        foreach ($lineItems as $line) {
            /** @var ServicePackage $package */
            $package = $line['service_package'];
            $requestedQty = max((float) $line['quantity'], 0);
            if ($requestedQty <= 0) {
                continue;
            }

            $requirements = [];
            $maxQty = INF;

            foreach ($package->materials as $material) {
                $perUnit = (float) $material->quantity_per_unit * (1 + ((float) $material->waste_percent / 100));
                if ($perUnit <= 0) {
                    continue;
                }

                $itemId = (int) $material->inventory_item_id;
                $available = (float) ($stockMap->get($itemId) ?? 0.0);
                $needed = $perUnit * $requestedQty;

                $maxQty = min($maxQty, $available / $perUnit);

                $requirements[] = [
                    'item_id' => $itemId,
                    'item_name' => $material->inventoryItem?->name ?? ('Item #'.$itemId),
                    'unit' => $material->inventoryItem?->unit ?? '',
                    'per_unit' => $perUnit,
                    'needed' => $needed,
                    'available' => $available,
                    'shortage' => max($needed - $available, 0),
                ];
            }

            if (empty($requirements)) {
                continue;
            }

            if (! is_finite($maxQty)) {
                $maxQty = 0;
            }

            $maxQty = max($maxQty, 0);
            $maxQtyRounded = floor($maxQty * 1000) / 1000;

            $isInsufficient = ($requestedQty - $maxQtyRounded) > 0.000001;
            if ($isInsufficient) {
                $issues[] = [
                    'line_no' => $line['line_no'],
                    'package_name' => $package->name,
                    'pricing_unit' => $package->pricing_unit,
                    'requested_qty' => $requestedQty,
                    'max_qty' => $maxQtyRounded,
                    'shortages' => array_values(array_filter($requirements, function (array $req) {
                        return $req['shortage'] > 0.000001;
                    })),
                ];
                continue;
            }

            foreach ($requirements as $req) {
                $remaining = (float) ($stockMap->get($req['item_id']) ?? 0) - ($req['per_unit'] * $requestedQty);
                $stockMap->put($req['item_id'], $remaining);
            }
        }

        return [
            'is_available' => empty($issues),
            'issues' => $issues,
        ];
    }

    public function applyStockOpname(StockOpname $opname, ?User $actor = null): void
    {
        DB::transaction(function () use ($opname, $actor) {
            $opname->loadMissing('items.inventoryItem');

            foreach ($opname->items as $opnameItem) {
                $diff = (float) $opnameItem->difference_stock;
                if ($diff == 0.0) {
                    continue;
                }

                $this->recordMovement([
                    'inventory_item_id' => $opnameItem->inventory_item_id,
                    'movement_type' => 'opname_adjustment',
                    'reference_type' => StockOpname::class,
                    'reference_id' => $opname->id,
                    'quantity_in' => $diff > 0 ? round($diff, 3) : 0,
                    'quantity_out' => $diff < 0 ? round(abs($diff), 3) : 0,
                    'unit_cost' => (float) $opnameItem->inventoryItem->average_cost,
                    'notes' => 'Penyesuaian stok opname '.$opname->opname_number,
                    'created_by' => $actor?->id,
                ]);
            }

            $opname->update([
                'status' => 'posted',
                'approved_by' => $actor?->id,
                'posted_at' => Carbon::now(),
            ]);
        });
    }
}
