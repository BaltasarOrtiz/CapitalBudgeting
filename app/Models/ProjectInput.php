<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ProjectInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'project_name',
        'period',
        'type',
        'amount'
    ];

    protected $casts = [
        'period' => 'integer',
        'amount' => 'decimal:2'
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }

    public function scopeCosts($query)
    {
        return $query->where('type', 'cost');
    }

    public function scopeRewards($query)
    {
        return $query->where('type', 'reward');
    }

    public function scopeByProject($query, string $projectName)
    {
        return $query->where('project_name', $projectName);
    }

    public function scopeByPeriod($query, int $period)
    {
        return $query->where('period', $period);
    }
}
