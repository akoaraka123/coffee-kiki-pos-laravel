<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'business_date',
        'started_at',
        'ended_at',
        'status',
        'opening_cash',
        'closing_cash',
    ];

    protected $casts = [
        'business_date' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shift_id', 'shift_id');
    }
}
