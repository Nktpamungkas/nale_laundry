<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'notes',
    ];

    /**
     * @return HasMany<LaundryOrder, $this>
     */
    public function laundryOrders(): HasMany
    {
        return $this->hasMany(LaundryOrder::class);
    }
}
