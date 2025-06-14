<?php

namespace App\Services;

use App\Models\Optimization;
use App\Models\OptimizationResult;
use App\Models\SelectedProject;
use App\Models\PeriodBalance;
use App\Models\PeriodCashFlow;
use Illuminate\Support\Facades\DB;

class ResultsProcessorService
{
    /**
     * Procesar todos los resultados de IBM CPLEX
     */
    public function processResults(Optimization $optimization, array $csvFiles): void
    {
        DB::transaction(function () use ($optimization, $csvFiles) {
            // Limpiar resultados anteriores (por si se re-ejecuta)
            $this->clearPreviousResults($optimization);

            // Procesar cada archivo CSV
            if (isset($csvFiles['SolutionResults.csv'])) {
                $this->processSolutionResults($optimization, $csvFiles['SolutionResults.csv']);
            }

            if (isset($csvFiles['SelectedProjectsOutput.csv'])) {
                $this->processSelectedProjects($optimization, $csvFiles['SelectedProjectsOutput.csv']);
            }

            if (isset($csvFiles['BalanceResults.csv'])) {
                $this->processBalanceResults($optimization, $csvFiles['BalanceResults.csv']);
            }

            if (isset($csvFiles['CashFlowResults.csv'])) {
                $this->processCashFlowResults($optimization, $csvFiles['CashFlowResults.csv']);
            }

            // Actualizar estado de la optimización
            $optimization->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        });
    }

    /**
     * Procesar SolutionResults.csv
     */
    private function processSolutionResults(Optimization $optimization, string $csvContent): void
    {
        $data = $this->parseCSVContent($csvContent);

        if (!empty($data)) {
            $row = $data[0]; // Primera fila de datos

            OptimizationResult::create([
                'optimization_id' => $optimization->id,
                'npv' => $row['NPV'],
                'final_balance' => $row['FinalBalance'],
                'initial_balance' => $row['InitialBalance'],
                'total_periods' => $row['TotalPeriods'],
                'total_projects' => $row['TotalProjects'],
                'projects_selected' => $row['ProjectsSelected'],
                'status' => $row['Status'],
            ]);
        }
    }

    /**
     * Procesar SelectedProjectsOutput.csv
     */
    private function processSelectedProjects(Optimization $optimization, string $csvContent): void
    {
        $data = $this->parseCSVContent($csvContent);

        foreach ($data as $row) {
            SelectedProject::create([
                'optimization_id' => $optimization->id,
                'project_name' => $row['ProjectName'],
                'start_period' => $row['StartPeriod'],
                'setup_cost' => $row['SetupCost'],
                'total_reward' => $row['TotalReward'],
                'npv_contribution' => $row['NPV_Contribution'],
            ]);
        }
    }

    /**
     * Procesar BalanceResults.csv
     */
    private function processBalanceResults(Optimization $optimization, string $csvContent): void
    {
        $data = $this->parseCSVContent($csvContent);

        foreach ($data as $row) {
            PeriodBalance::create([
                'optimization_id' => $optimization->id,
                'period' => $row['Period'],
                'balance' => $row['Balance'],
                'discounted_balance' => $row['DiscountedBalance'],
            ]);
        }
    }

    /**
     * Procesar CashFlowResults.csv
     */
    private function processCashFlowResults(Optimization $optimization, string $csvContent): void
    {
        $data = $this->parseCSVContent($csvContent);

        foreach ($data as $row) {
            PeriodCashFlow::create([
                'optimization_id' => $optimization->id,
                'period' => $row['Period'],
                'cash_in' => $row['CashIn'],
                'cash_out' => $row['CashOut'],
                'net_cash_flow' => $row['NetCashFlow'],
            ]);
        }
    }

    /**
     * Parsear contenido CSV y convertir a array asociativo
     */
    private function parseCSVContent(string $csvContent): array
    {
        $lines = str_getcsv($csvContent, "\n");

        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $data = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $data[] = array_combine($headers, $values);
            }
        }

        return $data;
    }

    /**
     * Limpiar resultados anteriores
     */
    private function clearPreviousResults(Optimization $optimization): void
    {
        $optimization->result()?->delete();
        $optimization->selectedProjects()->delete();
        $optimization->periodBalances()->delete();
        $optimization->periodCashFlows()->delete();
    }

    /**
     * Generar resumen de resultados
     */
    public function generateSummary(Optimization $optimization): array
    {
        $result = $optimization->result;

        if (!$result) {
            return ['error' => 'No hay resultados disponibles'];
        }

        return [
            'npv' => $result->npv,
            'final_balance' => $result->final_balance,
            'efficiency_rate' => $result->getEfficiencyRate(),
            'roi' => $result->getROI(),
            'selected_projects' => $optimization->selectedProjects()
                ->orderBy('start_period')
                ->get()
                ->map(fn($p) => [
                    'name' => $p->project_name,
                    'start_period' => $p->start_period,
                    'roi' => $p->getROI(),
                    'npv_contribution' => $p->npv_contribution
                ]),
            'cash_flow_summary' => $optimization->periodCashFlows()
                ->selectRaw('
                                                   SUM(cash_in) as total_cash_in,
                                                   SUM(cash_out) as total_cash_out,
                                                   SUM(net_cash_flow) as total_net_flow
                                               ')
                ->first()
        ];
    }
}
