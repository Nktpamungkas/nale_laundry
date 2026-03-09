<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaundryOrderItem extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'laundry_order_id',
        'service_package_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'material_cost',
        'labor_cost',
        'overhead_cost',
        'hpp_total',
        'profit_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'material_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'hpp_total' => 'decimal:2',
            'profit_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<LaundryOrder, $this>
     */
    public function laundryOrder(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class);
    }

    /**
     * @return BelongsTo<ServicePackage, $this>
     */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }
}
