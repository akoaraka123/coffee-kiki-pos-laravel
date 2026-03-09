<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneyInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'denomination',
        'quantity',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'denomination' => 'integer',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
