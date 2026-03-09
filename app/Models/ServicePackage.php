<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'code',
        'name',
        'pricing_unit',
        'sale_price',
        'labor_cost',
        'overhead_cost',
        'estimated_hours',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ServicePackageMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(ServicePackageMaterial::class);
    }

    /**
     * @return HasMany<LaundryOrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(LaundryOrderItem::class);
    }
}
