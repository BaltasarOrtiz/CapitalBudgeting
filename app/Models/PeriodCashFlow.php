<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodCashFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'period',
        'cash_in',
        'cash_out',
        'net_cash_flow',
    ];

    protected $casts = [
        'period' => 'integer',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'net_cash_flow' => 'decimal:2',
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }

    public function isPositive(): bool
    {
        return $this->net_cash_flow > 0;
    }

    public function isNegative(): bool
    {
        return $this->net_cash_flow < 0;
    }
}
