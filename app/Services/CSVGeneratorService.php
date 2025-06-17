<?php

namespace App\Services;

use App\Models\Optimization;
use Illuminate\Support\Facades\Log;

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
        // Obtener TODOS los costos de proyectos (sin filtros)
        $costs = $optimization->projectCosts()
            ->orderBy('project_name')
            ->orderBy('period')
            ->get()
            ->map(fn($cost) => [
                $cost->project_name,
                $cost->period,
                $cost->amount
            ])
            ->toArray();

        Log::info('Generando CSV de costos', [
            'optimization_id' => $optimization->id,
            'total_records' => count($costs),
            'unique_projects' => count(array_unique(array_column($costs, 0)))
        ]);

        $data = array_merge([['project', 'period', 'cost']], $costs);
        return $this->arrayToCSV($data);
    }

    /**
     * Generar ProjectRewards.csv
     */
    public function generateProjectRewardsCSV(Optimization $optimization): string
    {
        // Obtener TODAS las recompensas de proyectos (sin filtros)
        $rewards = $optimization->projectRewards()
            ->orderBy('project_name')
            ->orderBy('period')
            ->get()
            ->map(fn($reward) => [
                $reward->project_name,
                $reward->period,
                $reward->amount
            ])
            ->toArray();

        Log::info('Generando CSV de recompensas', [
            'optimization_id' => $optimization->id,
            'total_records' => count($rewards),
            'unique_projects' => count(array_unique(array_column($rewards, 0)))
        ]);

        $data = array_merge([['project', 'period', 'reward']], $rewards);
        return $this->arrayToCSV($data);
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
            ])
            ->toArray();

        $data = array_merge([['Period', 'MinBal']], $balances);
        return $this->arrayToCSV($data);
    }

    /**
     * Generar MustTakeOne.csv
     */
    public function generateMustTakeOneCSV(Optimization $optimization): string
    {
        // Aquí SÍ se filtran solo los proyectos que están en grupos
        $groups = $optimization->projectGroups()
            ->orderBy('group_id')
            ->orderBy('project_name')
            ->get()
            ->map(fn($group) => [
                $group->group_id,
                $group->project_name
            ])
            ->toArray();

        Log::info('Generando CSV de must-take-one', [
            'optimization_id' => $optimization->id,
            'total_records' => count($groups),
            'unique_groups' => count(array_unique(array_column($groups, 0)))
        ]);

        $data = array_merge([['group', 'project']], $groups);
        return $this->arrayToCSV($data);
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
}
