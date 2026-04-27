<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedMoneyInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'saved_at',
        'total_sales',
        'cash_total',
        'gcash_total',
        'total_verified',
        'difference',
        'status',
        'cash_breakdown',
        'gcash_details',
        'payment_entries',
        'shift_id',
        'business_date',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'saved_at' => 'datetime',
        'total_sales' => 'decimal:2',
        'cash_total' => 'decimal:2',
        'gcash_total' => 'decimal:2',
        'total_verified' => 'decimal:2',
        'difference' => 'decimal:2',
        'cash_breakdown' => 'array',
        'gcash_details' => 'array',
        'payment_entries' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
