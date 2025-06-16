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
    private  $token;

    public function __construct(IBMAuthService $authService)
    {
        $this->authService = $authService;
        $this->token = $this->authService->getToken();
        $this->endpoint = config('ibm.cos.endpoint');
        $this->bucketName = config('ibm.cos.bucket_name');
    }

    /**
     * Listar archivos en el bucket
     */
    public function listFiles(string $prefix = ''): array
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/{$this->bucketName}";

            $params = [];
            if ($prefix) {
                $params['prefix'] = $prefix;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
            ])->get($url, $params);

            if (!$response->successful()) {
                throw new Exception('Error listando archivos: ' . $response->body());
            }

            // Parsear XML response (IBM COS devuelve XML)
            return $this->parseListResponse($response->body());

        } catch (Exception $e) {
            Log::error('Error en COSService::listFiles', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Subir archivo al bucket
     */
    public function uploadFile(UploadedFile $file, string $filename): array
    {
        try {
            $filename = $filename ?: $file->getClientOriginalName();
            $token = $this->authService->requestToken();
            $url = "{$this->endpoint}/{$this->bucketName}/{$filename}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
                'Content-Type' => $file->getMimeType(),
            ])->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
              ->put($url);

            if (!$response->successful()) {
                throw new Exception('Error subiendo archivo: ' . $response->body());
            }

            return [
                'filename' => $filename,
                'size' => $file->getSize(),
                'url' => $url,
                'etag' => $response->header('ETag'),
            ];

        } catch (Exception $e) {
            Log::error('Error en COSService::uploadFile', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
                throw new Exception('Error descargando archivo: ' . $response->body());
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
     * Eliminar archivo del bucket
     */
    /* public function deleteFile(string $filename): bool
    {
        try {
            $token = $this->authService->getToken();
            $url = "{$this->endpoint}/{$this->bucketName}/{$filename}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'ibm-service-instance-id' => config('ibm.cos.service_instance_id'),
            ])->delete($url);

            return $response->successful();

        } catch (Exception $e) {
            Log::error('Error en COSService::deleteFile', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    } */

    /**
     * Obtener URL de archivo
     */
    public function getFileUrl(string $filename): string
    {
        return "{$this->endpoint}/{$this->bucketName}/{$filename}";
    }

    /**
     * Parsear respuesta XML de list objects
     */
    private function parseListResponse(string $xmlResponse): array
    {
        $xml = simplexml_load_string($xmlResponse);
        $files = [];

        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $files[] = [
                    'name' => (string) $content->Key,
                    'size' => (int) $content->Size,
                    'modified' => (string) $content->LastModified,
                    'etag' => (string) $content->ETag,
                ];
            }
        }

        return $files;
    }
}