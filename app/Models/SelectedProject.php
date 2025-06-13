<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectedProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'project_name',
        'start_period',
        'setup_cost',
        'total_reward',
        'npv_contribution'
    ];

    protected $casts = [
        'start_period' => 'integer',
        'setup_cost' => 'decimal:2',
        'total_reward' => 'decimal:2',
        'npv_contribution' => 'decimal:2'
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }

    public function getROI(): float
    {
        if ($this->setup_cost == 0) return 0;
        return (($this->total_reward - $this->setup_cost) / $this->setup_cost) * 100;
    }
}
