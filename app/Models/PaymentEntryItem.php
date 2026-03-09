<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_entry_id',
        'denomination',
        'quantity',
    ];

    protected $casts = [
        'denomination' => 'integer',
        'quantity' => 'integer',
    ];

    public function paymentEntry(): BelongsTo
    {
        return $this->belongsTo(PaymentEntry::class);
    }
}
