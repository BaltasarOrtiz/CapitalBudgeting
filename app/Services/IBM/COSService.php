<?php

namespace App\Services\IBM;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class COSService
{
    private IBMAuthService $authService;
    private string $endpoint;
    private string $bucketName;
    private $token;

    public function __construct(IBMAuthService $authService)
    {
        $this->authService = $authService;
        $this->token = $this->authService->getToken();
        $this->endpoint = config('ibm.cos.endpoint');
        $this->bucketName = config('ibm.cos.bucket_name');
    }

    /**
     * Subir contenido de string como archivo
     */
    public function uploadContent(string $content, string $filename, string $contentType = 'text/csv'): array
    {
        try {
            $url = "{$this->endpoint}/{$this->bucketName}/{$filename}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
                'Content-Type' => $contentType,
            ])
            ->withBody($content, $contentType)
            ->put($url);

            if (!$response->successful()) {
                throw new Exception('Error subiendo contenido: ' . $response->body());
            }

            return [
                'filename' => $filename,
                'size' => strlen($content),
                'url' => $url,
                'etag' => $response->header('ETag'),
            ];

        } catch (Exception $e) {
            Log::error('Error en COSService::uploadContent', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Descargar archivo del bucket
     */
    public function downloadFile(string $filename): string
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/{$this->bucketName}/{$filename}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
            ])->get($url);

            if (!$response->successful()) {
                throw new Exception("Error descargando archivo {$filename}: " . $response->body());
            }

            return $response->body();

        } catch (Exception $e) {
            Log::error('Error en COSService::downloadFile', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Descargar archivos de resultados de optimización
     * Devuelve un array con el contenido de cada archivo de resultado
     */
    public function downloadOptimizationResults(): array
    {
        $resultFiles = [
            'SolutionResults.csv',
            'SelectedProjectsOutput.csv',
            'BalanceResults.csv',
            'CashFlowResults.csv'
        ];

        $results = [];
        $errors = [];

        foreach ($resultFiles as $filename) {
            try {
                $content = $this->downloadFile($filename);
                $results[$filename] = $content;

                Log::info("Archivo de resultados descargado exitosamente: {$filename}");

            } catch (Exception $e) {
                $errors[$filename] = $e->getMessage();
                Log::warning("No se pudo descargar archivo de resultados: {$filename}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Si no se pudo descargar ningún archivo, lanzar excepción
        if (empty($results)) {
            throw new Exception('No se pudo descargar ningún archivo de resultados. Errores: ' . json_encode($errors));
        }

        return [
            'files' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Verificar si un archivo existe en el bucket
     */
    public function fileExists(string $filename): bool
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/{$this->bucketName}/{$filename}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
            ])->head($url);

            return $response->successful();

        } catch (Exception $e) {
            Log::debug("Error verificando existencia de archivo {$filename}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar qué archivos de resultados están disponibles
     */
    public function checkResultFilesAvailability(): array
    {
        $resultFiles = [
            'SolutionResults.csv',
            'SelectedProjectsOutput.csv',
            'BalanceResults.csv',
            'CashFlowResults.csv'
        ];

        $availability = [];

        foreach ($resultFiles as $filename) {
            $availability[$filename] = $this->fileExists($filename);
        }

        return $availability;
    }


    /**
     * Obtener URL de archivo
     */
    public function getFileUrl(string $filename): string
    {
        return "{$this->endpoint}/{$this->bucketName}/{$filename}";
    }

    /**
     * Convertir CSV a array asociativo
     */
    public function csvToArray(string $csvContent): array
    {
        $lines = array_map('str_getcsv', explode("\n", trim($csvContent)));

        if (empty($lines)) {
            return [];
        }

        $headers = array_shift($lines);

        // Limpiar headers de posibles caracteres extraños
        $headers = array_map('trim', $headers);

        $result = [];
        foreach ($lines as $line) {
            if (count($line) === count($headers)) {
                $row = array_combine($headers, $line);
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Procesar archivos de resultados y convertirlos a arrays
     */
    public function processOptimizationResults(): array
    {
        try {
            $downloadResult = $this->downloadOptimizationResults();
            $processedResults = [];
            foreach ($downloadResult['files'] as $filename => $content) {
                try {
                    $arrayData = $this->csvToArray($content);
                    $processedResults[$filename] = $arrayData;

                    Log::info("Archivo procesado exitosamente: {$filename}", [
                        'rows_count' => count($arrayData)
                    ]);

                } catch (Exception $e) {
                    Log::error("Error procesando archivo {$filename}: " . $e->getMessage());
                    throw new Exception("Error procesando {$filename}: " . $e->getMessage());
                }
            }

            return [
                'results' => $processedResults,
                'errors' => $downloadResult['errors'] ?? [],
                'processed_files' => array_keys($processedResults),
                'total_files' => count($processedResults)
            ];

        } catch (Exception $e) {
            Log::error('Error procesando resultados de optimización', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
