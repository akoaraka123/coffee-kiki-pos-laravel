<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'payment_type',
        'received_amount',
        'order_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'received_amount' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentEntryItem::class);
    }
}
