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
        'gcash_sender_name',
        'gcash_reference_number',
        'gcash_sender_mobile',
        'gcash_proof_image',
        'verified_at',
        'verified_by',
        'shift_id',
        'business_date',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'received_amount' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentEntryItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
