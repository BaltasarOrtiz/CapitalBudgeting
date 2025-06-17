<?php

namespace App\Http\Controllers;

use App\Models\Optimization;
use App\Services\CSVGeneratorService;
use App\Services\IBM\COSService;
use App\Services\IBM\WatsonMLService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\RedirectResponse;
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
            ->paginate(20);

        return Inertia::render('dashboard/Historial', [
            'optimizations' => $optimizations
        ]);
    }

    public function resultados()
    {
        $completedOptimizations = Optimization::with(['result', 'selectedProjects'])
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->paginate(10);

        return Inertia::render('dashboard/Resultados', [
            'optimizations' => $completedOptimizations
        ]);
    }

    /**
     * Flujo completo: Guardar datos + Generar CSVs + Subir a COS + Ejecutar job
     */
    public function store(Request $request): RedirectResponse | JsonResponse
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
                'execution_log' => "Job iniciado: {$jobResult['runtime_job_id']}"
            ]);

            DB::commit();

            return redirect()->route('optimizations.status', $optimization)->with([
                'success' => true,
                'message' => 'Optimización creada y ejecutada exitosamente',
                'optimization_id' => $optimization->id
            ]);
        } catch (Exception $e) {
            DB::rollback();

            // Si ya se creó la optimización, marcarla como fallida
            if (isset($optimization)) {
                $optimization->update([
                    'status' => 'failed',
                    'execution_log' => "Error: {$e->getMessage()}"
                ]);
            }

            Log::error('Error en optimización completa', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error procesando optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de optimización
     */
    public function status(Optimization $optimization): JsonResponse
    {
        try {
            $jobStatus = $this->watsonService->getJobStatus($optimization->url_status);

            if ($jobStatus['status'] === 'completed') {
                // Si el job está completo, actualizar la optimización
                $optimization->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'execution_log' => "Job completado: {$jobStatus['created_at']}"
                ]);
            } elseif ($jobStatus['status'] === 'failed') {
                $optimization->update([
                    'status' => 'failed',
                    'execution_log' => "Job fallido: {$jobStatus['created_at']}"
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $jobStatus['status'],
                'optimization' => $optimization
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error consultando estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar optimización específica con resultados
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
            'optimization' => $optimization
        ]);
    }

    /**
     * Listar optimizaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $optimizations = Optimization::with(['result'])
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
     * Guardar datos de entrada de forma simplificada
     */
    private function saveInputData(Optimization $optimization, array $data): void
    {
        // Guardar restricciones de balance mínimo
        if (isset($data['minBal'])) {
            foreach ($data['minBal'] as $minBal) {
                $optimization->balanceConstraints()->create([
                    'period' => $minBal['Period'],
                    'min_balance' => $minBal['MinBal']
                ]);
            }
        }

        // Guardar costos de proyectos
        if (isset($data['projectCosts'])) {
            foreach ($data['projectCosts'] as $cost) {
                $optimization->projectInputs()->create([
                    'project_name' => $cost['project'],
                    'period' => $cost['period'],
                    'type' => 'cost',
                    'amount' => $cost['cost']
                ]);
            }
        }

        // Guardar recompensas de proyectos
        if (isset($data['projectRewards'])) {
            foreach ($data['projectRewards'] as $reward) {
                $optimization->projectInputs()->create([
                    'project_name' => $reward['project'],
                    'period' => $reward['period'],
                    'type' => 'reward',
                    'amount' => $reward['reward']
                ]);
            }
        }

        // Guardar grupos must-take-one
        if (isset($data['mustTakeOne'])) {
            foreach ($data['mustTakeOne'] as $group) {
                $optimization->projectGroups()->create([
                    'group_id' => $group['group'],
                    'project_name' => $group['project']
                ]);
            }
        }
    }
}
