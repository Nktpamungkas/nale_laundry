<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'laundry_order_id',
        'payment_date',
        'amount',
        'method',
        'reference_no',
        'note',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'amount' => 'decimal:2',
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
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
