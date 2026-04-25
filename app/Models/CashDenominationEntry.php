<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDenominationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'entry_type',
        'denomination_1000',
        'denomination_500',
        'denomination_200',
        'denomination_100',
        'denomination_50',
        'denomination_20',
        'denomination_10',
        'denomination_5',
        'denomination_1',
        'total_cash_counted',
        'expected_cash',
        'expected_gcash',
        'verified_gcash',
        'cash_difference',
        'gcash_difference',
        'total_difference',
        'status',
        'gcash_verification_details',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'denomination_1000' => 'integer',
        'denomination_500' => 'integer',
        'denomination_200' => 'integer',
        'denomination_100' => 'integer',
        'denomination_50' => 'integer',
        'denomination_20' => 'integer',
        'denomination_10' => 'integer',
        'denomination_5' => 'integer',
        'denomination_1' => 'integer',
        'total_cash_counted' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'expected_gcash' => 'decimal:2',
        'verified_gcash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'gcash_difference' => 'decimal:2',
        'total_difference' => 'decimal:2',
        'gcash_verification_details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateTotalCashCounted(): float
    {
        return (
            ($this->denomination_1000 * 1000) +
            ($this->denomination_500 * 500) +
            ($this->denomination_200 * 200) +
            ($this->denomination_100 * 100) +
            ($this->denomination_50 * 50) +
            ($this->denomination_20 * 20) +
            ($this->denomination_10 * 10) +
            ($this->denomination_5 * 5) +
            ($this->denomination_1 * 1)
        );
    }

    public function getDenominationBreakdown(): array
    {
        return [
            1000 => $this->denomination_1000,
            500 => $this->denomination_500,
            200 => $this->denomination_200,
            100 => $this->denomination_100,
            50 => $this->denomination_50,
            20 => $this->denomination_20,
            10 => $this->denomination_10,
            5 => $this->denomination_5,
            1 => $this->denomination_1,
        ];
    }
}
