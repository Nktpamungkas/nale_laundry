<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit',
        'minimum_stock',
        'current_stock',
        'average_cost',
        'last_purchase_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'average_cost' => 'decimal:2',
            'last_purchase_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ServicePackageMaterial, $this>
     */
    public function servicePackageMaterials(): HasMany
    {
        return $this->hasMany(ServicePackageMaterial::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<StockOpnameItem, $this>
     */
    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }
}
