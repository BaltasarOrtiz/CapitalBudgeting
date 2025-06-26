<?php

namespace App\Http\Controllers;

use App\Http\Resources\OptimizationResource;
use App\Models\Optimization;
use App\Services\CSVGeneratorService;
use App\Services\IBM\COSService;
use App\Services\IBM\WatsonMLService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OptimizationController extends Controller
{
    public function __construct(
        private CSVGeneratorService $csvGenerator,
        private COSService $cosService,
        private WatsonMLService $watsonService,
    ) {}

    public function inicio()
    {
        return Inertia::render('dashboard/Inicio');
    }

    public function historial()
    {
        $optimizations = Optimization::with(['result'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return Inertia::render('dashboard/Historial', [
            'optimizations' => OptimizationResource::collection($optimizations)
                ->additional([
                    'current_page' => $optimizations->currentPage(),
                    'last_page' => $optimizations->lastPage(),
                    'per_page' => $optimizations->perPage(),
                    'total' => $optimizations->total(),
                    'from' => $optimizations->firstItem(),
                    'to' => $optimizations->lastItem(),
                    'has_more_pages' => $optimizations->hasMorePages(),
                    'has_pages' => $optimizations->hasPages(),
                    'links' => $optimizations->linkCollection()->toArray(),
                ]),
        ]);
    }

    public function resultados()
    {
        // Buscar la optimización más reciente del usuario con todas sus relaciones
        $currentOptimization = Optimization::with([
            'result',
            'selectedProjects',
            'periodBalances',
            'periodCashFlows',
        ])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->first();

        return Inertia::render('dashboard/Resultados', [
            'currentOptimization' => $currentOptimization,
        ]);
    }

    /**
     * Flujo completo: Guardar datos + Generar CSVs + Subir a COS + Ejecutar job
     */
    public function store(Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // 1. Crear optimización con parámetros principales
            $parameters = $request->input('parameters');

            $optimization = Optimization::create([
                'description' => $parameters['Description'] ?? 'Optimización sin descripción',
                'user_id' => auth()->id(),
                'total_periods' => $parameters['T'],
                'discount_rate' => $parameters['Rate'],
                'initial_balance' => $parameters['InitBal'],
                'nb_must_take_one' => $parameters['NbMustTakeOne'] ?? 0,
                'status' => 'running',
            ]);

            // 2. Guardar datos de entrada de forma directa
            $this->saveInputData($optimization, $request->all());

            // 3. Generar archivos CSV
            $csvFiles = $this->csvGenerator->generateAllInputFiles($optimization);

            // 4. Subir archivos a IBM COS
            $uploadedFiles = [];
            foreach ($csvFiles as $filename => $content) {
                $result = $this->cosService->uploadContent($content, $filename);
                $uploadedFiles[] = $filename;
            }

            // 5. Ejecutar job en IBM Watson ML
            $jobResult = $this->watsonService->executeJob($optimization);

            // 6. Actualizar optimización con información del job
            $optimization->update([
                'input_files_path' => json_encode($uploadedFiles),
                'execution_log' => "Job iniciado: {$jobResult['runtime_job_id']}",
            ]);

            DB::commit();

            return redirect()->route('dashboard.resultados')->with([
                'success' => true,
                'message' => 'Optimización creada y ejecutada exitosamente',
                'optimization_id' => $optimization->id,
            ]);
        } catch (Exception $e) {
            DB::rollback();

            // Si ya se creó la optimización, marcarla como fallida
            if (isset($optimization)) {
                $optimization->update([
                    'status' => 'failed',
                    'execution_log' => "Error: {$e->getMessage()}",
                ]);
            }

            Log::error('Error en optimización completa', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            // En caso de error, también redirigir a resultados con mensaje de error
            return redirect()->route('dashboard.resultados')->with([
                'success' => false,
                'error' => 'Error procesando optimización',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consultar estado de optimización
     */
    public function status(Optimization $optimization): JsonResponse
    {
        try {
            $jobStatus = $this->watsonService->getJobStatus($optimization->url_status);

            if ($jobStatus === 'completed') {
                // Si el job está completo, actualizar la optimización
                $optimization->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'execution_log' => 'Job completado exitosamente',
                ]);

                // Procesar resultados del COS
                $results = $this->processOptimizationResults($optimization);

                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'optimization' => $optimization->fresh(['result', 'selectedProjects', 'periodBalances', 'periodCashFlows']),
                    'results' => $results,
                ]);
            } elseif ($jobStatus === 'failed') {
                $optimization->update([
                    'status' => 'failed',
                    'execution_log' => 'Job fallido',
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'failed',
                    'optimization' => $optimization->fresh(),
                ]);
            }

            // Si aún está corriendo
            return response()->json([
                'success' => true,
                'status' => 'running',
                'optimization' => $optimization,
            ]);
        } catch (Exception $e) {
            Log::error('Error consultando estado de optimización', [
                'optimization_id' => $optimization->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error consultando estado',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar resultados de la optimización desde COS
     */
    private function processOptimizationResults(Optimization $optimization): array
    {
        try {
            // Usar el nuevo método del servicio COS
            $cosResults = $this->cosService->processOptimizationResults();

            Log::info('Resultados de COS procesados', [
                'optimization_id' => $optimization->id,
                'files_processed' => $cosResults['processed_files'],
                'total_files' => $cosResults['total_files'],
            ]);

            // Guardar resultados en la base de datos
            foreach ($cosResults['results'] as $filename => $data) {
                $this->saveOptimizationResults($optimization, $filename, $data);
            }

            return [
                'success' => true,
                'files_processed' => $cosResults['processed_files'],
                'total_files' => $cosResults['total_files'],
                'results' => $cosResults['results'],
                'errors' => $cosResults['errors'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error('Error procesando resultados de COS', [
                'optimization_id' => $optimization->id,
                'error' => $e->getMessage(),
            ]);

            // Verificar disponibilidad de archivos para diagnóstico
            try {
                $availability = $this->cosService->checkResultFilesAvailability();
                Log::info('Disponibilidad de archivos de resultados', [
                    'optimization_id' => $optimization->id,
                    'availability' => $availability,
                ]);
            } catch (Exception $availabilityError) {
                Log::warning('No se pudo verificar disponibilidad de archivos', [
                    'error' => $availabilityError->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Guardar resultados en la base de datos
     */
    private function saveOptimizationResults(Optimization $optimization, string $filename, array $data): void
    {
        try {
            switch ($filename) {
                case 'SolutionResults.csv':
                    if (! empty($data)) {
                        $optimization->result()->updateOrCreate([], [
                            'npv' => $data[0]['NPV'] ?? 0,
                            'final_balance' => $data[0]['FinalBalance'] ?? 0,
                            'initial_balance' => $data[0]['InitialBalance'] ?? 0,
                            'total_periods' => $data[0]['TotalPeriods'] ?? 0,
                            'total_projects' => $data[0]['TotalProjects'] ?? 0,
                            'projects_selected' => $data[0]['ProjectsSelected'] ?? 0,
                            'status' => $data[0]['Status'] ?? 'UNKNOWN',
                        ]);
                    }
                    break;

                case 'SelectedProjectsOutput.csv':
                    $optimization->selectedProjects()->delete();
                    foreach ($data as $project) {
                        $optimization->selectedProjects()->create([
                            'project_name' => $project['ProjectName'] ?? '',
                            'start_period' => $project['StartPeriod'] ?? 0,
                            'setup_cost' => $project['SetupCost'] ?? 0,
                            'total_reward' => $project['TotalReward'] ?? 0,
                            'npv_contribution' => $project['NPV_Contribution'] ?? 0,
                        ]);
                    }
                    break;

                case 'BalanceResults.csv':
                    $optimization->periodBalances()->delete();
                    foreach ($data as $balance) {
                        $optimization->periodBalances()->create([
                            'period' => $balance['Period'] ?? 0,
                            'balance' => $balance['Balance'] ?? 0,
                            'discounted_balance' => $balance['DiscountedBalance'] ?? 0,
                        ]);
                    }
                    break;

                case 'CashFlowResults.csv':
                    $optimization->periodCashFlows()->delete();
                    foreach ($data as $cashFlow) {
                        $optimization->periodCashFlows()->create([
                            'period' => $cashFlow['Period'] ?? 0,
                            'cash_in' => $cashFlow['CashIn'] ?? 0,
                            'cash_out' => $cashFlow['CashOut'] ?? 0,
                            'net_cash_flow' => $cashFlow['NetCashFlow'] ?? 0,
                        ]);
                    }
                    break;
            }

            Log::info("Datos guardados en BD para archivo: {$filename}", [
                'optimization_id' => $optimization->id,
                'records_count' => count($data),
            ]);
        } catch (Exception $e) {
            Log::error("Error guardando datos de {$filename} en BD", [
                'optimization_id' => $optimization->id,
                'error' => $e->getMessage(),
                'data_sample' => array_slice($data, 0, 2), // Mostrar muestra de los datos para debug
            ]);
            throw $e;
        }
    }

    /**
     * Guardar datos de entrada de forma simplificada
     */
    private function saveInputData(Optimization $optimization, array $data): void
    {
        Log::info('Guardando datos de entrada', [
            'optimization_id' => $optimization->id,
            'data_keys' => array_keys($data),
        ]);

        // Guardar restricciones de balance mínimo
        if (isset($data['minBal'])) {
            foreach ($data['minBal'] as $minBal) {
                $optimization->balanceConstraints()->create([
                    'period' => $minBal['Period'],
                    'min_balance' => $minBal['MinBal'],
                ]);
            }
            Log::info('Guardadas restricciones de balance mínimo', [
                'count' => count($data['minBal']),
            ]);
        }

        // Guardar costos de TODOS los proyectos (no solo los seleccionados)
        if (isset($data['projectCosts'])) {
            foreach ($data['projectCosts'] as $cost) {
                $optimization->projectInputs()->create([
                    'project_name' => $cost['project'],
                    'period' => $cost['period'],
                    'type' => 'cost',
                    'amount' => $cost['cost'],
                ]);
            }
            Log::info('Guardados costos de proyectos', [
                'count' => count($data['projectCosts']),
                'projects' => array_unique(array_column($data['projectCosts'], 'project')),
            ]);
        }

        // Guardar recompensas de TODOS los proyectos (no solo los seleccionados)
        if (isset($data['projectRewards'])) {
            foreach ($data['projectRewards'] as $reward) {
                $optimization->projectInputs()->create([
                    'project_name' => $reward['project'],
                    'period' => $reward['period'],
                    'type' => 'reward',
                    'amount' => $reward['reward'],
                ]);
            }
            Log::info('Guardadas recompensas de proyectos', [
                'count' => count($data['projectRewards']),
                'projects' => array_unique(array_column($data['projectRewards'], 'project')),
            ]);
        }

        // Guardar grupos must-take-one (estos sí son solo los seleccionados)
        if (isset($data['mustTakeOne'])) {
            foreach ($data['mustTakeOne'] as $group) {
                $optimization->projectGroups()->create([
                    'group_id' => $group['group'],
                    'project_name' => $group['project'],
                ]);
            }
            Log::info('Guardados grupos must-take-one', [
                'count' => count($data['mustTakeOne']),
                'groups' => array_unique(array_column($data['mustTakeOne'], 'group')),
            ]);
        }

        // Verificar que se guardaron datos de todos los proyectos esperados
        $totalProjects = $optimization->projectInputs()
            ->distinct('project_name')
            ->count();

        Log::info('Resumen de datos guardados', [
            'optimization_id' => $optimization->id,
            'total_unique_projects' => $totalProjects,
            'total_cost_records' => $optimization->projectCosts()->count(),
            'total_reward_records' => $optimization->projectRewards()->count(),
            'total_groups' => $optimization->projectGroups()->distinct('group_id')->count(),
        ]);
    }
}
