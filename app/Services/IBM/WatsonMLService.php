<?php

namespace App\Services\IBM;

use App\Models\Optimization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WatsonMLService
{
    private IBMAuthService $authService;
    private string $endpoint;
    private string $spaceId;
    private string $jobId;

    public function __construct(IBMAuthService $authService)
    {
        $this->authService = $authService;
        $this->endpoint = config('ibm.watson_ml.endpoint');
        $this->spaceId = config('ibm.watson_ml.space_id');
        $this->jobId = config('ibm.watson_ml.job_id');
    }

    /**
     * Ejecutar un job de optimización
     */
    public function executeJob(Optimization $optimization): array
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/v2/jobs/{$this->jobId}/runs?space_id={$this->spaceId}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])
            ->withBody('{}', 'application/json')
            ->post($url);


            if (!$response->successful()) {
                throw new Exception('Error ejecutando job: ' . $response->body());
            }

            $data = $response->json();
            $runtimeJobId = $data['entity']['job_run']['runtime_job_id'] ?? null;

            if (!$runtimeJobId) {
                throw new Exception('runtime_job_id no encontrado en respuesta');
            }

            $optimization->update([
                'url_status' => $data['href'],
            ]);

            return [
                'runtime_job_id' => $runtimeJobId,
                'status' => $data['entity']['job_run']['state'] ?? 'unknown',
                'created_at' => $data['metadata']['created_at'] ?? now(),
            ];

        } catch (Exception $e) {
            Log::error('Error en WatsonMLService::executeJob', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Consultar estado de un job
     */
    public function getJobStatus(string $url_status): string
    {
        try {
            $token = $this->authService->getToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get($url_status);

            if (!$response->successful()) {
                throw new Exception('Error consultando estado del job: ' . $response->body());
            }

            $data = $response->json();
            $entity = $data['entity']['job_run'] ?? [];

            return strtolower($entity['state']);

        } catch (Exception $e) {
            Log::error('Error en WatsonMLService::getJobStatus', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener logs de un job
     */
    /* public function getJobLogs(string $runtimeJobId): array
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/v2/jobs/runs/{$runtimeJobId}/logs";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get($url, ['space_id' => $this->spaceId]);

            if (!$response->successful()) {
                throw new Exception('Error obteniendo logs del job: ' . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Error en WatsonMLService::getJobLogs', [
                'runtime_job_id' => $runtimeJobId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    } */

    /**
     * Esperar a que termine un job (polling)
     */
    public function waitForJobCompletion(string $runtimeJobId): array
    {
        $maxChecks = config('ibm.watson_ml.max_status_checks');
        $interval = config('ibm.watson_ml.status_check_interval');
        $checks = 0;

        while ($checks < $maxChecks) {
            $status = $this->getJobStatus($runtimeJobId);

            // Estados finales
            if (in_array($status['status'], ['completed', 'failed', 'canceled'])) {
                return $status;
            }

            sleep($interval);
            $checks++;
        }

        throw new Exception("Job timeout después de {$maxChecks} verificaciones");
    }

    /**
     * Ejecutar job y esperar resultados (método combinado)
     */
    public function runJobAndWait(array $jobParams = []): array
    {
        $execution = $this->executeJob($jobParams);
        return $this->waitForJobCompletion($execution['runtime_job_id']);
    }
}
