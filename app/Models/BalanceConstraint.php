<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceConstraint extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'period',
        'min_balance'
    ];

    protected $casts = [
        'period' => 'integer',
        'min_balance' => 'decimal:2'
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }
}
