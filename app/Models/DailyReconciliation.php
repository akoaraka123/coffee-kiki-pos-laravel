<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReconciliation extends Model
{
    protected $fillable = [
        'user_id',
        'selected_date',
        'total_sales',
        'expected_cash',
        'verified_cash',
        'expected_gcash',
        'verified_gcash',
        'total_expected',
        'total_verified',
        'difference',
        'status',
        'cash_denomination_breakdown',
        'verified_gcash_transactions',
        'number_of_gcash_transactions',
    ];

    protected $casts = [
        'selected_date' => 'date',
        'total_sales' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'verified_cash' => 'decimal:2',
        'expected_gcash' => 'decimal:2',
        'verified_gcash' => 'decimal:2',
        'total_expected' => 'decimal:2',
        'total_verified' => 'decimal:2',
        'difference' => 'decimal:2',
        'cash_denomination_breakdown' => 'array',
        'verified_gcash_transactions' => 'array',
        'number_of_gcash_transactions' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
