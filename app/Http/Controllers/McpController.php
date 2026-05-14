<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;
use Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest;
use Nisalatp\DynamicReportGenerator\Services\GovernanceManager;
use Nisalatp\DynamicReportGenerator\Models\VirtualAttribute;
use Nisalatp\DynamicReportGenerator\Models\ReportLog;
use App\Models\Role;

/**
 * MCP Tool Endpoints Controller
 *
 * Exposes the Dynamic Report Generator's capabilities as REST endpoints,
 * mirroring the MCP tool definitions from the package documentation.
 */
class McpController extends Controller
{
    // ─── Schema Discovery ────────────────────────────────────────

    /**
     * MCP Tool: get_available_models
     * Returns all reportable model short names.
     */
    public function getAvailableModels(): JsonResponse
    {
        $models = DynamicReport::getAvailableModels();

        // Return short class names for the AI to reference
        $shortNames = array_map(function ($fqcn) {
            return class_basename($fqcn);
        }, $models);

        return response()->json([
            'models' => array_values($shortNames),
            'full_class_map' => array_combine($shortNames, $models),
        ]);
    }

    /**
     * MCP Tool: get_model_attributes
     * Returns all queryable columns (physical + virtual) for a model.
     */
    public function getModelAttributes(string $model): JsonResponse
    {
        $fqcn = $this->resolveModelClass($model);
        if (!$fqcn) {
            return response()->json(['error' => "Model '{$model}' not found"], 404);
        }

        $attributes = DynamicReport::getModelAttributes($fqcn);

        return response()->json([
            'model' => $model,
            'attributes' => $attributes,
        ]);
    }

    /**
     * MCP Tool: get_model_relationships
     * Returns all models this model can join to, with direction info.
     */
    public function getModelRelationships(string $model): JsonResponse
    {
        $fqcn = $this->resolveModelClass($model);
        if (!$fqcn) {
            return response()->json(['error' => "Model '{$model}' not found"], 404);
        }

        $relationships = DynamicReport::getConnectedModels($fqcn);

        // Format for AI consumption
        $formatted = [];
        foreach ($relationships as $relatedModel => $link) {
            $formatted[class_basename($relatedModel)] = [
                'type' => $link->type ?? 'Unknown',
                'methodName' => $link->methodName ?? null,
                'direction' => $link->direction ?? 'forward',
            ];
        }

        return response()->json([
            'model' => $model,
            'relationships' => $formatted,
        ]);
    }

    /**
     * MCP Tool: get_max_filter_depth
     */
    public function getMaxFilterDepth(): JsonResponse
    {
        return response()->json([
            'max_filter_depth' => DynamicReport::getMaxFilterDepth(),
        ]);
    }

    // ─── Report Generation ───────────────────────────────────────

    /**
     * MCP Tool: generate_dynamic_report
     * Generates a report from an AST payload.
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $payload = $this->normalizePayload($payload);
            $payload = $this->expandModelClasses($payload);

            // Resolve ALS subjects from role_id if provided
            $subjects = $this->resolveSubjects($request->header('X-Role-Id'));

            $reportRequest = ReportBuilderRequest::fromPayload($payload);
            $query = DynamicReport::generate($reportRequest, $subjects);

            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count(),
                'sql' => DynamicReport::toRawSql($query),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'type' => class_basename($e),
            ], 422);
        }
    }

    /**
     * MCP Tool: debug_report_sql
     * Compiles AST to SQL without executing.
     */
    public function debugSql(Request $request): JsonResponse
    {
        try {
            $payload = $this->normalizePayload($this->expandModelClasses($request->all()));
            $payload = $this->expandModelClasses($payload);
            $subjects = $this->resolveSubjects($request->header('X-Role-Id'));

            $reportRequest = ReportBuilderRequest::fromPayload($payload);
            $query = DynamicReport::generate($reportRequest, $subjects);

            return response()->json([
                'sql' => DynamicReport::toRawSql($query),
                'bindings' => $query->getBindings(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: explain_join_plan
     * Returns the BFS join plan without executing.
     */
    public function explainJoinPlan(Request $request): JsonResponse
    {
        try {
            $payload = $this->normalizePayload($request->all());
            $payload = $this->expandModelClasses($payload);
            $reportRequest = ReportBuilderRequest::fromPayload($payload);
            $joinPlan = DynamicReport::explainJoinPlan($reportRequest);

            $steps = [];
            foreach ($joinPlan->steps as $step) {
                $steps[] = [
                    'fromModel' => class_basename($step->fromModel),
                    'toModel' => class_basename($step->toModel),
                    'relationType' => $step->relationType,
                    'direction' => $step->direction ?? 'forward',
                    'localKey' => $step->localKey,
                    'foreignKey' => $step->foreignKey,
                ];
            }

            return response()->json(['steps' => $steps]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ─── Report Persistence ──────────────────────────────────────

    /**
     * MCP Tool: save_dynamic_report
     */
    public function saveReport(Request $request): JsonResponse
    {
        try {
            $payload = $this->normalizePayload($request->input('payload', []));
            $payload = $this->expandModelClasses($payload);
            $reportRequest = ReportBuilderRequest::fromPayload($payload);

            $saved = DynamicReport::saveReport(
                $request->input('name', 'Untitled Report'),
                $reportRequest,
                $request->input('user_id'),
                $request->input('description', '')
            );

            return response()->json([
                'success' => true,
                'report' => $saved,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: get_saved_reports (list all)
     */
    public function getSavedReports(): JsonResponse
    {
        $reports = DynamicReport::getSavedReports();
        return response()->json(['reports' => $reports]);
    }

    /**
     * MCP Tool: get_saved_report_config
     */
    public function getSavedReportConfig(int $id): JsonResponse
    {
        try {
            $config = DynamicReport::loadToEditor($id);
            return response()->json([
                'id' => $id,
                'payload' => json_decode($config->toJson(), true),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * MCP Tool: run_saved_report
     */
    public function runSavedReport(Request $request, int $id): JsonResponse
    {
        try {
            $query = DynamicReport::loadAndGenerate($id, $request->input('user_id'));
            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count(),
                'sql' => DynamicReport::toRawSql($query),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'type' => class_basename($e),
            ], 422);
        }
    }

    /**
     * MCP Tool: assign_report
     */
    public function assignReport(Request $request): JsonResponse
    {
        try {
            $reportId = $request->input('report_id');
            $userId = $request->input('user_id');
            // Assuming auth()->id() or 1 for demo purposes
            DynamicReport::assignReport($reportId, $userId, 1);
            return response()->json(['status' => 'assigned', 'report_id' => $reportId, 'user_id' => $userId]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: unassign_report
     */
    public function unassignReport(Request $request): JsonResponse
    {
        try {
            $reportId = $request->input('report_id');
            $userId = $request->input('user_id');
            // Assuming auth()->id() or 1 for demo purposes
            DynamicReport::unassignReport($reportId, $userId, 1);
            return response()->json(['status' => 'unassigned', 'report_id' => $reportId, 'user_id' => $userId]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: get_my_reports
     */
    public function getMyReports(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id', 1); // Defaulting to 1 for demo
            $reports = DynamicReport::getAssignedReports($userId);
            return response()->json(['reports' => $reports]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ─── Governance & Security ───────────────────────────────────

    /**
     * MCP Tool: get_security_matrix
     */
    public function getSecurityMatrix(Request $request): JsonResponse
    {
        try {
            $modelClass = $this->resolveModelClass($request->input('model_class', ''));
            $subjectClass = $request->input('subject_class', Role::class);
            $subjectId = (int) $request->input('subject_id', 0);

            if (!$modelClass) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            $matrix = GovernanceManager::getMatrix($modelClass, $subjectClass, $subjectId);

            return response()->json($matrix);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: save_security_matrix
     */
    public function saveSecurityMatrix(Request $request): JsonResponse
    {
        try {
            $modelClass = $this->resolveModelClass($request->input('model_class', ''));
            $subjectClass = $request->input('subject_class', Role::class);
            $subjectId = (int) $request->input('subject_id');
            $isReportable = (bool) $request->input('is_reportable', true);
            $attributes = $request->input('attributes', []);
            $authId = (int) $request->input('auth_id', 1);

            if (!$modelClass) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            GovernanceManager::saveMatrix($modelClass, $subjectClass, $subjectId, $isReportable, $attributes, $authId);

            return response()->json(['success' => true, 'message' => 'Security matrix saved.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: register_virtual_attribute
     */
    public function registerVirtualAttribute(Request $request): JsonResponse
    {
        try {
            $modelClass = $this->resolveModelClass($request->input('model', ''));
            if (!$modelClass) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            $va = VirtualAttribute::create([
                'name' => $request->input('name'),
                'base_model' => $modelClass,
                'sql_fragment' => $request->input('sql_fragment'),
                'dependencies' => $request->input('dependencies', []),
            ]);

            return response()->json(['success' => true, 'virtual_attribute' => $va]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: get_audit_logs
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        $query = ReportLog::query()->orderBy('created_at', 'desc');

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->has('report_id')) {
            $query->where('saved_report_id', $request->input('report_id'));
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        return response()->json(['logs' => $query->limit(50)->get()]);
    }

    /**
     * MCP Tool: restrict_model
     */
    public function restrictModel(Request $request): JsonResponse
    {
        try {
            $modelClass = $this->resolveModelClass($request->input('model_class', ''));
            if (!$modelClass) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            DynamicReport::restrictModel($modelClass);
            return response()->json(['success' => true, 'message' => "Model {$modelClass} restricted."]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * MCP Tool: unrestrict_model
     */
    public function unrestrictModel(Request $request): JsonResponse
    {
        try {
            $modelClass = $this->resolveModelClass($request->input('model_class', ''));
            if (!$modelClass) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            DynamicReport::unrestrictModel($modelClass);
            return response()->json(['success' => true, 'message' => "Model {$modelClass} unrestricted."]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get all available roles for the role switcher UI.
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::all(['id', 'name', 'description']);
        return response()->json(['roles' => $roles]);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Resolve a short model name (e.g. "User") to its FQCN (e.g. "App\Models\User").
     */
    private function resolveModelClass(string $shortName): ?string
    {
        if (empty($shortName)) return null;

        // Already fully qualified
        if (class_exists($shortName)) return $shortName;

        // Try App\Models\ prefix
        $fqcn = "App\\Models\\{$shortName}";
        if (class_exists($fqcn)) return $fqcn;

        // Search in available models
        $allModels = DynamicReport::getAvailableModels();
        foreach ($allModels as $model) {
            if (class_basename($model) === $shortName) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Expand short model names to FQCNs in a payload recursively.
     * Also normalizes 'modelClass' → 'model' since ReportBuilderRequest::fromPayload
     * reads the 'model' key.
     */
    private function expandModelClasses(array $payload): array
    {
        // Expand baseModel
        if (isset($payload['baseModel'])) {
            $payload['baseModel'] = $this->resolveModelClass($payload['baseModel']) ?? $payload['baseModel'];
        }

        // Expand targetModels
        if (isset($payload['targetModels']) && is_array($payload['targetModels'])) {
            $payload['targetModels'] = array_map(function ($m) {
                return $this->resolveModelClass($m) ?? $m;
            }, $payload['targetModels']);
        }

        // Normalize & expand model references in attribute arrays.
        // DeepSeek may send 'modelClass' or 'model' — we normalize to 'model'
        // which is what ReportBuilderRequest::fromPayload expects.
        $expandAttributes = function (&$items) {
            if (!is_array($items)) return;
            foreach ($items as &$item) {
                // Normalize modelClass → model
                if (isset($item['modelClass']) && !isset($item['model'])) {
                    $item['model'] = $item['modelClass'];
                    unset($item['modelClass']);
                }
                // Resolve short name to FQCN
                if (isset($item['model'])) {
                    $item['model'] = $this->resolveModelClass($item['model']) ?? $item['model'];
                }
                // Handle nested 'attribute' objects (used by groupBys, aggregates, sorts)
                if (isset($item['attribute'])) {
                    if (isset($item['attribute']['modelClass']) && !isset($item['attribute']['model'])) {
                        $item['attribute']['model'] = $item['attribute']['modelClass'];
                        unset($item['attribute']['modelClass']);
                    }
                    if (isset($item['attribute']['model'])) {
                        $item['attribute']['model'] = $this->resolveModelClass($item['attribute']['model']) ?? $item['attribute']['model'];
                    }
                }
            }
        };

        if (isset($payload['selectedAttributes'])) $expandAttributes($payload['selectedAttributes']);
        if (isset($payload['groupBys'])) $expandAttributes($payload['groupBys']);
        if (isset($payload['aggregates'])) $expandAttributes($payload['aggregates']);
        if (isset($payload['sorts'])) $expandAttributes($payload['sorts']);

        // Expand filters recursively
        $expandFilters = function (&$node) use (&$expandFilters) {
            if (!is_array($node)) return;
            // Normalize modelClass → model in filter attributes
            if (isset($node['modelClass']) && !isset($node['model'])) {
                $node['model'] = $node['modelClass'];
                unset($node['modelClass']);
            }
            if (isset($node['model'])) {
                $node['model'] = $this->resolveModelClass($node['model']) ?? $node['model'];
            }
            if (isset($node['attribute'])) {
                if (isset($node['attribute']['modelClass']) && !isset($node['attribute']['model'])) {
                    $node['attribute']['model'] = $node['attribute']['modelClass'];
                    unset($node['attribute']['modelClass']);
                }
                if (isset($node['attribute']['model'])) {
                    $node['attribute']['model'] = $this->resolveModelClass($node['attribute']['model']) ?? $node['attribute']['model'];
                }
            }
            if (isset($node['children']) && is_array($node['children'])) {
                foreach ($node['children'] as &$child) {
                    $expandFilters($child);
                }
            }
        };

        if (isset($payload['innerFilters'])) {
            $expandFilters($payload['innerFilters']);
        }
        if (isset($payload['outerFilters'])) {
            $expandFilters($payload['outerFilters']);
        }

        return $payload;
    }

    /**
     * Normalize the AST payload from the AI's nested format into the flat format
     * expected by ReportBuilderRequest::fromPayload.
     *
     * DeepSeek sends: {"attribute": {"modelClass": "User", "column": "name"}, "function": "SUM", "alias": "x"}
     * fromPayload expects: {"model": "User", "column": "name", "function": "SUM", "alias": "x"}
     */
    private function normalizePayload(array $payload): array
    {
        // Flatten selectedAttributes
        if (isset($payload['selectedAttributes']) && is_array($payload['selectedAttributes'])) {
            $payload['selectedAttributes'] = array_map(function ($attr) {
                return $this->flattenAttribute($attr);
            }, $payload['selectedAttributes']);
        }

        // Flatten groupBys
        if (isset($payload['groupBys']) && is_array($payload['groupBys'])) {
            $payload['groupBys'] = array_map(function ($gb) {
                return $this->flattenAttribute($gb);
            }, $payload['groupBys']);
        }

        // Flatten aggregates
        if (isset($payload['aggregates']) && is_array($payload['aggregates'])) {
            $payload['aggregates'] = array_map(function ($agg) {
                $flat = $this->flattenAttribute($agg);
                // Ensure 'function' key is preserved at top level
                if (isset($agg['function'])) {
                    $flat['function'] = $agg['function'];
                }
                if (isset($agg['alias'])) {
                    $flat['alias'] = $agg['alias'];
                }
                return $flat;
            }, $payload['aggregates']);
        }

        // Flatten sorts
        if (isset($payload['sorts']) && is_array($payload['sorts'])) {
            $payload['sorts'] = array_map(function ($sort) {
                $flat = $this->flattenAttribute($sort);
                if (isset($sort['direction'])) {
                    $flat['direction'] = $sort['direction'];
                }
                return $flat;
            }, $payload['sorts']);
        }

        // Normalize filters
        if (isset($payload['innerFilters'])) {
            $payload['innerFilters'] = $this->normalizeFilters($payload['innerFilters']);
        }
        if (isset($payload['outerFilters'])) {
            $payload['outerFilters'] = $this->normalizeFilters($payload['outerFilters']);
        }

        return $payload;
    }

    /**
     * Flatten a nested attribute structure.
     * Input:  {"attribute": {"modelClass": "User", "column": "name", "type": "string"}, "alias": "x"}
     * Output: {"model": "User", "column": "name", "type": "string", "alias": "x"}
     */
    private function flattenAttribute(array $item): array
    {
        // If it has a nested 'attribute' key, pull fields up
        if (isset($item['attribute']) && is_array($item['attribute'])) {
            $nested = $item['attribute'];
            unset($item['attribute']);
            // Merge nested fields into parent
            $item = array_merge($nested, $item);
        }

        // Normalize modelClass -> model
        if (isset($item['modelClass']) && !isset($item['model'])) {
            $item['model'] = $item['modelClass'];
            unset($item['modelClass']);
        }

        return $item;
    }

    /**
     * Recursively normalize filter nodes to flatten nested 'attribute' objects.
     */
    private function normalizeFilters(array $node): array
    {
        if (isset($node['type']) && $node['type'] === 'group') {
            if (isset($node['children']) && is_array($node['children'])) {
                $node['children'] = array_map(function ($child) {
                    return $this->normalizeFilters($child);
                }, $node['children']);
            }
            return $node;
        }

        // Leaf node — flatten the attribute
        if (isset($node['attribute']) && is_array($node['attribute'])) {
            $nested = $node['attribute'];
            unset($node['attribute']);
            $node = array_merge($nested, $node);
        }

        // Normalize modelClass -> model
        if (isset($node['modelClass']) && !isset($node['model'])) {
            $node['model'] = $node['modelClass'];
            unset($node['modelClass']);
        }

        return $node;
    }

    /**
     * Resolve ALS subjects from a role ID (simulating different user perspectives).
     */
    private function resolveSubjects(?string $roleId): ?array
    {
        if (!$roleId) return null;

        $role = Role::find((int) $roleId);
        if (!$role) return null;

        // For the demo, we treat the Role itself as the subject for ALS
        return [$role];
    }
}
