<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_order_id',
        'phone',
        'event',
        'message_text',
        'request_payload',
        'response_status',
        'response_body',
        'is_success',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'is_success' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LaundryOrder, $this>
     */
    public function laundryOrder(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class);
    }
}
