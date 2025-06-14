<?php

namespace App\Http\Controllers\IBM;

use App\Http\Controllers\Controller;
use App\Services\IBM\WatsonMLService;
use App\Services\IBM\COSService;
use App\Services\CSVGeneratorService;
use App\Models\Optimization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\ResultsProcessorService;

class WatsonMLController extends Controller
{
    public function __construct(
        private WatsonMLService $watsonService,
        private COSService $cosService,
        private CSVGeneratorService $csvGenerator
    ) {}

    /**
     * Ejecutar un job básico
     */
    public function executeJob(Request $request): JsonResponse
    {
        try {
            $jobParams = $request->all();
            $result = $this->watsonService->executeJob($jobParams);

            return response()->json([
                'success' => true,
                'message' => 'Job ejecutado exitosamente',
                'job' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error ejecutando job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de un job
     */
    public function getJobStatus(string $jobId): JsonResponse
    {
        try {
            $status = $this->watsonService->getJobStatus($jobId);

            return response()->json([
                'success' => true,
                'job' => $status
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error consultando estado del job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs de un job
     */
    public function getJobLogs(string $jobId): JsonResponse
    {
        try {
            $logs = $this->watsonService->getJobLogs($jobId);

            return response()->json([
                'success' => true,
                'logs' => $logs
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo logs del job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resultados desde COS
     */
    public function getResults(Request $request): JsonResponse
    {
        try {
            $filename = $request->query('filename', 'res-nodeVehicles.csv');
            $content = $this->cosService->downloadFile($filename);

            // Parsear CSV a JSON
            $lines = str_getcsv($content, "\n");
            $headers = str_getcsv(array_shift($lines));
            $data = [];

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                $values = str_getcsv($line);
                if (count($values) === count($headers)) {
                    $data[] = array_combine($headers, $values);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Resultados procesados correctamente',
                'data' => $data,
                'filename' => $filename
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo resultados',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar optimización completa (subir CSVs + ejecutar + obtener resultados)
     */
    public function runOptimization(Optimization $optimization): JsonResponse
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

            // 2. Generar archivos CSV
            $csvFiles = $this->csvGenerator->generateAllInputFiles($optimization);

            // 3. Subir archivos a COS
            $uploadedFiles = [];
            foreach ($csvFiles as $filename => $content) {
                $result = $this->cosService->uploadContent($content, $filename);
                $uploadedFiles[] = $result;
            }

            // 4. Ejecutar job de optimización
            $jobResult = $this->watsonService->executeJob([
                'optimization_id' => $optimization->id,
                'files' => array_keys($csvFiles)
            ]);

            // 5. Actualizar estado de la optimización
            $optimization->update([
                'status' => 'running',
                'input_files_path' => json_encode($uploadedFiles),
                'execution_log' => "Job iniciado: {$jobResult['runtime_job_id']}"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Optimización iniciada exitosamente',
                'optimization' => $optimization,
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
     * Consultar estado de optimización (combina job status + procesamiento de resultados)
     */
    public function getOptimizationStatus(Optimization $optimization): JsonResponse
    {
        try {
            // Obtener runtime_job_id del log de ejecución
            $log = $optimization->execution_log;
            preg_match('/Job iniciado: (.+)/', $log, $matches);

            if (!isset($matches[1])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Runtime job ID no encontrado'
                ], 404);
            }

            $runtimeJobId = $matches[1];
            $jobStatus = $this->watsonService->getJobStatus($runtimeJobId);

            // Si el job terminó exitosamente, procesar resultados
            if ($jobStatus['status'] === 'completed' && $optimization->status !== 'completed') {
                $this->processOptimizationResults($optimization);
            }

            return response()->json([
                'success' => true,
                'optimization' => $optimization->fresh(),
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
     * Procesar resultados de optimización desde COS
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
                app(ResultsProcessorService::class)->processResults($optimization, $csvContents);

                $optimization->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'output_files_path' => json_encode(array_keys($csvContents))
                ]);
            }

        } catch (Exception $e) {
            $optimization->update([
                'status' => 'failed',
                'execution_log' => $optimization->execution_log . "\nError procesando resultados: " . $e->getMessage()
            ]);
        }
    }
}