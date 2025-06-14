<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;

class Optimization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
        'total_periods',
        'discount_rate',
        'initial_balance',
        'nb_must_take_one',
        'input_files_path',
        'output_files_path',
        'execution_log',
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'discount_rate' => 'decimal:6',
        'initial_balance' => 'decimal:2',
        'total_periods' => 'integer',
        'nb_must_take_one' => 'integer'
    ];

    // Relaciones - Datos de entrada
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectInputs(): HasMany
    {
        return $this->hasMany(ProjectInput::class);
    }

    public function projectCosts(): HasMany
    {
        return $this->hasMany(ProjectInput::class)->where('type', 'cost');
    }

    public function projectRewards(): HasMany
    {
        return $this->hasMany(ProjectInput::class)->where('type', 'reward');
    }

    public function balanceConstraints(): HasMany
    {
        return $this->hasMany(BalanceConstraint::class);
    }

    public function projectGroups(): HasMany
    {
        return $this->hasMany(ProjectGroup::class);
    }

    // Relaciones - Datos de salida
    public function result(): HasOne
    {
        return $this->hasOne(OptimizationResult::class);
    }

    public function selectedProjects(): HasMany
    {
        return $this->hasMany(SelectedProject::class);
    }

    public function periodBalances(): HasMany
    {
        return $this->hasMany(PeriodBalance::class);
    }

    public function periodCashFlows(): HasMany
    {
        return $this->hasMany(PeriodCashFlow::class);
    }

    // Estados (solo lógica del modelo)
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Métodos auxiliares del modelo
    public function getProjectNames(): array
    {
        return $this->projectInputs()
                   ->distinct('project_name')
                   ->pluck('project_name')
                   ->toArray();
    }

    public function getTotalProjectsCount(): int
    {
        return count($this->getProjectNames());
    }
}
