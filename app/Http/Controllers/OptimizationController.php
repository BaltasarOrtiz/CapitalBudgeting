<?php
namespace App\Http\Controllers;

use App\Models\Optimization;
use App\Services\CSVGeneratorService;
use App\Services\ResultsProcessorService;
use App\Http\Requests\StoreOptimizationRequest;
use App\Http\Resources\OptimizationResource;
use App\Jobs\ProcessOptimizationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OptimizationController extends Controller
{
    public function __construct(
        private CSVGeneratorService $csvGenerator,
        private ResultsProcessorService $resultsProcessor
    ) {}

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

        return response()->json($optimizations);
    }

    /**
     * Crear nueva optimización
     */
    public function store(/* StoreOptimizationRequest */ $request): JsonResponse
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
                throw new \Exception('Errores en los datos: ' . implode(', ', $errors));
            }

            DB::commit();

            // TODO
            // 4. Disparar job asíncrono para procesamiento
            // ProcessOptimizationJob::dispatch($optimization);

            return response()->json([
                'message' => 'Optimización creada y enviada a procesamiento',
                'optimization' => $optimization, // new OptimizationResource($optimization),
                'preview' => $this->csvGenerator->generatePreview($optimization)
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Mostrar optimización específica
     */
    public function show(Optimization $optimization): JsonResponse
    {
        // $this->authorize('view', $optimization);

        $optimization->load([
            'result',
            'selectedProjects',
            'periodBalances',
            'periodCashFlows',
        ]);

        return response()->json([
            'optimization' => $optimization, // new OptimizationResource($optimization),
            'summary' => $optimization->isCompleted()
                ? $this->resultsProcessor->generateSummary($optimization)
                : null
        ]);
    }

    /**
     * Generar y descargar CSVs de entrada
     */
    public function downloadInputFiles(Optimization $optimization): JsonResponse
    {
        // $this->authorize('view', $optimization);

        try {
            $csvFiles = $this->csvGenerator->generateAllInputFiles($optimization);

            // En un caso real, aquí subirías a IBM COS y devolverías URLs
            return response()->json([
                'message' => 'Archivos CSV generados exitosamente',
                'files' => array_keys($csvFiles),
                'preview' => $this->csvGenerator->generatePreview($optimization)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener preview de datos sin generar archivos
     */
    public function preview(Optimization $optimization): JsonResponse
    {
        // $this->authorize('view', $optimization);

        return response()->json([
            'preview' => $this->csvGenerator->generatePreview($optimization),
            'validation_errors' => $this->csvGenerator->validateInputData($optimization)
        ]);
    }

    /**
     * Almacenar datos de entrada de la optimización
     */
    private function storeInputData(Optimization $optimization, /* StoreOptimizationRequest */ $request): void
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