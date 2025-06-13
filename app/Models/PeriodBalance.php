<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'period',
        'balance',
        'discounted_balance'
    ];

    protected $casts = [
        'period' => 'integer',
        'balance' => 'decimal:2',
        'discounted_balance' => 'decimal:2'
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }
}