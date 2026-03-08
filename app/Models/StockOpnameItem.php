<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'inventory_item_id',
        'system_stock',
        'actual_stock',
        'difference_stock',
        'adjustment_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_stock' => 'decimal:3',
            'actual_stock' => 'decimal:3',
            'difference_stock' => 'decimal:3',
            'adjustment_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<StockOpname, $this>
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
