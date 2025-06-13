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

    // Métodos para generar CSVs de entrada
    public function generateParametersCSV(): string
    {
        $data = [
            ['Parameter', 'Value'],
            ['T', $this->total_periods],
            ['Rate', $this->discount_rate],
            ['InitBal', $this->initial_balance],
            ['NbMustTakeOne', $this->nb_must_take_one]
        ];

        return $this->arrayToCSV($data);
    }

    public function generateProjectCostsCSV(): string
    {
        $costs = $this->projectCosts()
                     ->orderBy('project_name')
                     ->orderBy('period')
                     ->get()
                     ->map(fn($c) => [
                         $c->project_name,
                         $c->period,
                         $c->amount
                     ]);

        return $this->arrayToCSV([['project', 'period', 'cost']] + $costs->toArray());
    }

    public function generateProjectRewardsCSV(): string
    {
        $rewards = $this->projectRewards()
                       ->orderBy('project_name')
                       ->orderBy('period')
                       ->get()
                       ->map(fn($r) => [
                           $r->project_name,
                           $r->period,
                           $r->amount
                       ]);

        return $this->arrayToCSV([['project', 'period', 'reward']] + $rewards->toArray());
    }

    public function generateMinBalCSV(): string
    {
        $balances = $this->balanceConstraints()
                        ->orderBy('period')
                        ->get()
                        ->map(fn($b) => [
                            $b->period,
                            $b->min_balance
                        ]);

        return $this->arrayToCSV([['Period', 'MinBal']] + $balances->toArray());
    }

    public function generateMustTakeOneCSV(): string
    {
        $groups = $this->projectGroups()
                      ->orderBy('group_id')
                      ->orderBy('project_name')
                      ->get()
                      ->map(fn($g) => [
                          $g->group_id,
                          $g->project_name
                      ]);

        return $this->arrayToCSV([['group', 'project']] + $groups->toArray());
    }

    // Método auxiliar para generar CSV
    private function arrayToCSV(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    // Estados
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

    // Obtener lista única de proyectos
    public function getProjectNames(): array
    {
        return $this->projectInputs()
                   ->distinct('project_name')
                   ->pluck('project_name')
                   ->toArray();
    }
}
