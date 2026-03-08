<?php

namespace App\Services;

use App\Models\ServicePackage;

class HppCalculator
{
    /**
     * @return array<string, float>
     */
    public function calculateLine(ServicePackage $servicePackage, float $quantity): array
    {
        $materialCost = 0.0;

        foreach ($servicePackage->materials as $material) {
            $baseQty = (float) $material->quantity_per_unit;
            $wasteFactor = 1 + (((float) $material->waste_percent) / 100);
            $requiredQty = $baseQty * $quantity * $wasteFactor;
            $unitCost = (float) $material->inventoryItem?->average_cost;
            $materialCost += $requiredQty * $unitCost;
        }

        $laborCost = (float) $servicePackage->labor_cost * $quantity;
        $overheadCost = (float) $servicePackage->overhead_cost * $quantity;
        $hppTotal = $materialCost + $laborCost + $overheadCost;

        return [
            'material_cost' => round($materialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'overhead_cost' => round($overheadCost, 2),
            'hpp_total' => round($hppTotal, 2),
        ];
    }
}
