<?php

namespace App\Http\Controllers\IBM;

use App\Http\Controllers\Controller;
use App\Services\IBM\COSService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class COSController extends Controller
{
    public function __construct(
        private COSService $cosService
    ) {}

    /**
     * Listar archivos en el bucket
     */
    public function listFiles(Request $request): JsonResponse
    {
        try {
            $prefix = $request->query('prefix', '');
            $files = $this->cosService->listFiles($prefix);

            return response()->json([
                'success' => true,
                'files' => $files,
                'count' => count($files)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error listando archivos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir archivo al bucket
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'filename' => 'nullable|string|max:255'
        ]);

        try {
            $file = $request->file('file');
            $filename = $request->input('filename');

            $result = $this->cosService->uploadFile($file, $filename);

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente',
                'file' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error subiendo archivo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo del bucket
     */
    public function downloadFile(string $filename): JsonResponse
    {
        try {
            $content = $this->cosService->downloadFile($filename);

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'content' => base64_encode($content),
                'size' => strlen($content)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error descargando archivo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar archivo del bucket
     */
    public function deleteFile(string $filename): JsonResponse
    {
        try {
            $success = $this->cosService->deleteFile($filename);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo eliminado exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo eliminar el archivo'
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error eliminando archivo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener URL de archivo
     */
    public function getFileUrl(string $filename): JsonResponse
    {
        try {
            $url = $this->cosService->getFileUrl($filename);

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'url' => $url
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}