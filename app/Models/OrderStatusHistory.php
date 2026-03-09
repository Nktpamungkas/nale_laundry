<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'laundry_order_id',
        'status',
        'note',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
