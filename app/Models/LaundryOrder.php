<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaundryOrder extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'order_number',
        'customer_id',
        'received_at',
        'due_at',
        'status',
        'status_note',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'hpp_total',
        'payment_status',
        'paid_amount',
        'pickup_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'due_at' => 'datetime',
            'pickup_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'hpp_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<LaundryOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LaundryOrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<WhatsappNotification, $this>
     */
    public function whatsappNotifications(): HasMany
    {
        return $this->hasMany(WhatsappNotification::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
