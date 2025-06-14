<?php
namespace App\Services;

use App\Models\Optimization;
use Illuminate\Support\Collection;

class CSVGeneratorService
{
    /**
     * Generar todos los archivos CSV necesarios para IBM CPLEX
     */
    public function generateAllInputFiles(Optimization $optimization): array
    {
        return [
            'parameters.csv' => $this->generateParametersCSV($optimization),
            'ProjectCosts.csv' => $this->generateProjectCostsCSV($optimization),
            'ProjectRewards.csv' => $this->generateProjectRewardsCSV($optimization),
            'MinBal.csv' => $this->generateMinBalanceCSV($optimization),
            'MustTakeOne.csv' => $this->generateMustTakeOneCSV($optimization),
        ];
    }

    /**
     * Generar parameters.csv
     */
    public function generateParametersCSV(Optimization $optimization): string
    {
        $data = [
            ['Parameter', 'Value'],
            ['T', $optimization->total_periods],
            ['Rate', $optimization->discount_rate],
            ['InitBal', $optimization->initial_balance],
            ['NbMustTakeOne', $optimization->nb_must_take_one]
        ];

        return $this->arrayToCSV($data);
    }

    /**
     * Generar ProjectCosts.csv
     */
    public function generateProjectCostsCSV(Optimization $optimization): string
    {
        $costs = $optimization->projectCosts()
            ->orderBy('project_name')
            ->orderBy('period')
            ->get()
            ->map(fn($cost) => [
                $cost->project_name,
                $cost->period,
                $cost->amount
            ]);

        $header = [['project', 'period', 'cost']];
        return $this->arrayToCSV($header->concat($costs)->toArray());
    }

    /**
     * Generar ProjectRewards.csv
     */
    public function generateProjectRewardsCSV(Optimization $optimization): string
    {
        $rewards = $optimization->projectRewards()
            ->orderBy('project_name')
            ->orderBy('period')
            ->get()
            ->map(fn($reward) => [
                $reward->project_name,
                $reward->period,
                $reward->amount
            ]);

        $header = [['project', 'period', 'reward']];
        return $this->arrayToCSV($header->concat($rewards)->toArray());
    }

    /**
     * Generar MinBal.csv
     */
    public function generateMinBalanceCSV(Optimization $optimization): string
    {
        $balances = $optimization->balanceConstraints()
            ->orderBy('period')
            ->get()
            ->map(fn($balance) => [
                $balance->period,
                $balance->min_balance
            ]);

        $header = [['Period', 'MinBal']];
        return $this->arrayToCSV($header->concat($balances)->toArray());
    }

    /**
     * Generar MustTakeOne.csv
     */
    public function generateMustTakeOneCSV(Optimization $optimization): string
    {
        $groups = $optimization->projectGroups()
            ->orderBy('group_id')
            ->orderBy('project_name')
            ->get()
            ->map(fn($group) => [
                $group->group_id,
                $group->project_name
            ]);

        $header = [['group', 'project']];
        return $this->arrayToCSV($header->concat($groups)->toArray());
    }

    /**
     * Validar datos de entrada antes de generar CSVs
     */
    public function validateInputData(Optimization $optimization): array
    {
        $errors = [];

        // Validar que hay proyectos
        if ($optimization->projectInputs()->count() === 0) {
            $errors[] = 'No hay proyectos definidos';
        }

        // Validar que todos los proyectos tienen costos
        $projectNames = $optimization->getProjectNames();
        foreach ($projectNames as $project) {
            $hasCosts = $optimization->projectCosts()
                ->where('project_name', $project)
                ->exists();
            if (!$hasCosts) {
                $errors[] = "Proyecto {$project} no tiene costos definidos";
            }
        }

        // Validar períodos
        if ($optimization->total_periods <= 0) {
            $errors[] = 'Número de períodos debe ser mayor a 0';
        }

        // Validar balance inicial
        if ($optimization->initial_balance <= 0) {
            $errors[] = 'Balance inicial debe ser mayor a 0';
        }

        // Validar grupos must-take-one
        if ($optimization->nb_must_take_one > 0) {
            $maxGroup = $optimization->projectGroups()->max('group_id') ?? 0;
            if ($maxGroup != $optimization->nb_must_take_one) {
                $errors[] = 'Número de grupos must-take-one no coincide con los grupos definidos';
            }
        }

        return $errors;
    }

    /**
     * Convertir array a formato CSV
     */
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

    /**
     * Generar preview de los datos para debugging
     */
    public function generatePreview(Optimization $optimization): array
    {
        return [
            'parameters' => [
                'total_periods' => $optimization->total_periods,
                'discount_rate' => $optimization->discount_rate,
                'initial_balance' => $optimization->initial_balance,
                'nb_must_take_one' => $optimization->nb_must_take_one,
            ],
            'projects' => $optimization->getProjectNames(),
            'costs_count' => $optimization->projectCosts()->count(),
            'rewards_count' => $optimization->projectRewards()->count(),
            'balance_constraints_count' => $optimization->balanceConstraints()->count(),
            'project_groups_count' => $optimization->projectGroups()->count(),
        ];
    }
}
