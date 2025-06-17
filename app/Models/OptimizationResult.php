<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptimizationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'npv',
        'final_balance',
        'initial_balance',
        'total_periods',
        'total_projects',
        'projects_selected',
        'status'
    ];

    protected $casts = [
        'npv' => 'decimal:2',
        'final_balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
        'total_periods' => 'integer',
        'total_projects' => 'integer',
        'projects_selected' => 'integer'
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }

    public function getEfficiencyRate(): float
    {
        if ($this->total_projects == 0) return 0;
        return ($this->projects_selected / $this->total_projects) * 100;
    }
}
