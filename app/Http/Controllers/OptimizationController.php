<?php

namespace App\Http\Controllers;

use App\Models\Optimization;
use App\Services\CSVGeneratorService;
use App\Services\ResultsProcessorService;
use App\Services\IBM\COSService;
use App\Services\IBM\WatsonMLService;
use App\Http\Requests\StoreOptimizationRequest;
use App\Http\Resources\OptimizationResource;
use App\Jobs\ProcessOptimizationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Inertia\Inertia;

class OptimizationController extends Controller
{
  /*   public function __construct(
        private CSVGeneratorService $csvGenerator,
        private ResultsProcessorService $resultsProcessor,
        private COSService $cosService,
        private WatsonMLService $watsonService
    ) {}
 */
    public function inicio()
    {
        return Inertia::render('dashboard/Inicio');
    }

    public function historial()
    {
        // Aquí puedes cargar datos del historial desde la base de datos
        return Inertia::render('dashboard/Historial');
    }

    public function resultados()
    {
        // Aquí puedes cargar los resultados desde la base de datos
        return Inertia::render('dashboard/Resultados');
    }

    /**
     * Listar optimizaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $optimizations = Optimization::with(['user:id,name', 'result'])
            ->where('user_id', auth()->id())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $optimizations
        ]);
    }

    /**
     * Crear nueva optimización
     */
    public function store(/* StoreOptimizationRequest */ Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Crear optimización
            $optimization = Optimization::create([
                'name' => $request->name,
                'description' => $request->description,
                'user_id' => auth()->id(),
                'total_periods' => $request->total_periods,
                'discount_rate' => $request->discount_rate,
                'initial_balance' => $request->initial_balance,
                'nb_must_take_one' => $request->nb_must_take_one ?? 0,
                'status' => 'pending',
            ]);

            // 2. Almacenar datos de entrada
            $this->storeInputData($optimization, $request);

            // 3. Validar datos usando el servicio
            $errors = $this->csvGenerator->validateInputData($optimization);
            if (!empty($errors)) {
                throw new Exception('Errores en los datos: ' . implode(', ', $errors));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Optimización creada exitosamente',
                'optimization' => $optimization,
                'preview' => $this->csvGenerator->generatePreview($optimization)
            ], 201);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Mostrar optimización específica
     */
    public function show(Optimization $optimization): JsonResponse
    {

        $optimization->load([
            'result',
            'selectedProjects',
            'periodBalances',
            'periodCashFlows',
        ]);

        return response()->json([
            'success' => true,
            'optimization' => $optimization,
            'summary' => $optimization->isCompleted()
                ? $this->resultsProcessor->generateSummary($optimization)
                : null
        ]);
    }

    /**
     * Ejecutar optimización completa (nuevo endpoint integrado con IBM)
     */
    public function execute(Optimization $optimization): JsonResponse
    {

        try {
            // 1. Validar datos de entrada
            $errors = $this->csvGenerator->validateInputData($optimization);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos de entrada inválidos',
                    'validation_errors' => $errors
                ], 422);
            }

            // 2. Actualizar estado a "running"
            $optimization->update(['status' => 'running']);

            // 3. Generar archivos CSV
            $csvFiles = $this->csvGenerator->generateAllInputFiles($optimization);

            // 4. Subir archivos a IBM COS
            $uploadedFiles = [];
            foreach ($csvFiles as $filename => $content) {
                $result = $this->cosService->uploadContent($content, $filename);
                $uploadedFiles[] = $result;
            }

            // 5. Ejecutar job de optimización en Watson ML
            $jobResult = $this->watsonService->executeJob([
                'optimization_id' => $optimization->id,
                'files' => array_keys($csvFiles)
            ]);

            // 6. Actualizar optimización con información del job
            $optimization->update([
                'input_files_path' => json_encode($uploadedFiles),
                'execution_log' => "Job iniciado: {$jobResult['runtime_job_id']}"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Optimización ejecutada exitosamente',
                'optimization' => $optimization->fresh(),
                'job' => $jobResult,
                'uploaded_files' => $uploadedFiles
            ]);

        } catch (Exception $e) {
            // Actualizar estado a fallido
            $optimization->update([
                'status' => 'failed',
                'execution_log' => "Error: {$e->getMessage()}"
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error ejecutando optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de optimización en tiempo real
     */
    public function status(Optimization $optimization): JsonResponse
    {

        try {
            // Extraer runtime_job_id del log
            $log = $optimization->execution_log;
            if (!$log || !preg_match('/Job iniciado: (.+)/', $log, $matches)) {
                return response()->json([
                    'success' => true,
                    'optimization' => $optimization,
                    'job_status' => null,
                    'message' => 'Optimización no ejecutada aún'
                ]);
            }

            $runtimeJobId = trim($matches[1]);
            $jobStatus = $this->watsonService->getJobStatus($runtimeJobId);

            // Si el job terminó exitosamente, procesar resultados automáticamente
            if ($jobStatus['status'] === 'completed' && $optimization->status === 'running') {
                $this->processOptimizationResults($optimization);
                $optimization = $optimization->fresh();
            }

            // Si el job falló, actualizar estado
            if ($jobStatus['status'] === 'failed' && $optimization->status === 'running') {
                $optimization->update([
                    'status' => 'failed',
                    'execution_log' => $optimization->execution_log . "\nJob falló en Watson ML"
                ]);
            }

            return response()->json([
                'success' => true,
                'optimization' => $optimization,
                'job_status' => $jobStatus
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error consultando estado de optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar y descargar CSVs de entrada
     */
    public function downloadInputFiles(Optimization $optimization): JsonResponse
    {

        try {
            $csvFiles = $this->csvGenerator->generateAllInputFiles($optimization);

            return response()->json([
                'success' => true,
                'message' => 'Archivos CSV generados exitosamente',
                'files' => $csvFiles,
                'preview' => $this->csvGenerator->generatePreview($optimization)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivos de resultados desde IBM COS
     */
    public function downloadResults(Optimization $optimization): JsonResponse
    {

        try {
            if (!$optimization->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La optimización no ha sido completada'
                ], 400);
            }

            // Archivos de resultado esperados
            $resultFiles = [
                'SolutionResults.csv',
                'SelectedProjectsOutput.csv',
                'BalanceResults.csv',
                'CashFlowResults.csv'
            ];

            $downloadedFiles = [];
            foreach ($resultFiles as $filename) {
                try {
                    $content = $this->cosService->downloadFile($filename);
                    $downloadedFiles[$filename] = base64_encode($content);
                } catch (Exception $e) {
                    // Archivo no encontrado, continuar con los siguientes
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Archivos de resultados descargados',
                'files' => $downloadedFiles,
                'optimization' => $optimization
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error descargando resultados',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener preview de datos sin generar archivos
     */
    public function preview(Optimization $optimization): JsonResponse
    {

        return response()->json([
            'success' => true,
            'preview' => $this->csvGenerator->generatePreview($optimization),
            'validation_errors' => $this->csvGenerator->validateInputData($optimization)
        ]);
    }

    /**
     * Cancelar optimización en ejecución
     */
    public function cancel(Optimization $optimization): JsonResponse
    {

        try {
            if (!$optimization->isRunning()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La optimización no está en ejecución'
                ], 400);
            }

            // TODO: Implementar cancelación del job en Watson ML si es posible

            $optimization->update([
                'status' => 'cancelled',
                'execution_log' => $optimization->execution_log . "\nOptimización cancelada por el usuario"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Optimización cancelada exitosamente',
                'optimization' => $optimization
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error cancelando optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar optimización
     */
    public function destroy(Optimization $optimization): JsonResponse
    {

        try {
            // Solo permitir eliminar si no está en ejecución
            if ($optimization->isRunning()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede eliminar una optimización en ejecución'
                ], 400);
            }

            $optimization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Optimización eliminada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error eliminando optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs de ejecución desde Watson ML
     */
    public function logs(Optimization $optimization): JsonResponse
    {

        try {
            $log = $optimization->execution_log;
            if (!$log || !preg_match('/Job iniciado: (.+)/', $log, $matches)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay logs de ejecución disponibles'
                ], 404);
            }

            $runtimeJobId = trim($matches[1]);
            $logs = $this->watsonService->getJobLogs($runtimeJobId);

            return response()->json([
                'success' => true,
                'logs' => $logs,
                'local_log' => $optimization->execution_log
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo logs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar resultados de optimización desde IBM COS
     */
    private function processOptimizationResults(Optimization $optimization): void
    {
        try {
            // Archivos de resultado esperados
            $resultFiles = [
                'SolutionResults.csv',
                'SelectedProjectsOutput.csv',
                'BalanceResults.csv',
                'CashFlowResults.csv'
            ];

            $csvContents = [];
            foreach ($resultFiles as $filename) {
                try {
                    $content = $this->cosService->downloadFile($filename);
                    $csvContents[$filename] = $content;
                } catch (Exception $e) {
                    Log::warning("Archivo de resultado no encontrado: {$filename}");
                }
            }

            if (!empty($csvContents)) {
                // Usar el servicio de procesamiento de resultados existente
                $this->resultsProcessor->processResults($optimization, $csvContents);

                $optimization->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'output_files_path' => json_encode(array_keys($csvContents)),
                    'execution_log' => $optimization->execution_log . "\nResultados procesados exitosamente"
                ]);
            }

        } catch (Exception $e) {
            $optimization->update([
                'status' => 'failed',
                'execution_log' => $optimization->execution_log . "\nError procesando resultados: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Almacenar datos de entrada de la optimización
     */
    private function storeInputData(Optimization $optimization, Request $request): void
    {
        // Almacenar costos y recompensas de proyectos
        if ($request->has('project_data')) {
            foreach ($request->project_data as $projectData) {
                $optimization->projectInputs()->create($projectData);
            }
        }

        // Almacenar restricciones de balance
        if ($request->has('balance_constraints')) {
            foreach ($request->balance_constraints as $constraint) {
                $optimization->balanceConstraints()->create($constraint);
            }
        }

        // Almacenar grupos must-take-one
        if ($request->has('project_groups')) {
            foreach ($request->project_groups as $group) {
                $optimization->projectGroups()->create($group);
            }
        }
    }
}
