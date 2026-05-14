<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;

/**
 * MCP Orchestrator Controller
 *
 * The AI brain of the demo. Receives natural language, translates it
 * to MCP tool calls via DeepSeek, executes them against the ReportMaker
 * engine, and returns the summarized response.
 */
class McpAgentController extends Controller
{
    public function index()
    {
        return view('admin.mcp-agent');
    }

    private string $apiKey;
    private string $apiUrl = 'https://api.deepseek.com/chat/completions';
    private string $model = 'deepseek-chat';

    /**
     * MCP Tool definitions provided to the LLM as function-calling tools.
     */
    private function getToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_available_models',
                    'description' => 'Get a list of all queryable data models in the system.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_model_attributes',
                    'description' => 'Get all queryable columns (both physical and virtual) for a specific model. Virtual attributes are prefixed with va: and represent computed SQL metrics.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'model' => ['type' => 'string', 'description' => 'Short model name, e.g. User, Order, Product'],
                        ],
                        'required' => ['model'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_model_relationships',
                    'description' => 'Get all models that this model can join to, and the type of relationship (BelongsTo, HasMany, etc.). Each edge includes a direction field.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'model' => ['type' => 'string', 'description' => 'Short model name'],
                        ],
                        'required' => ['model'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_max_filter_depth',
                    'description' => 'Get the maximum allowed nesting depth for AND/OR filter groups.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_dynamic_report',
                    'description' => 'Generate a data report by providing an AST payload. The engine will auto-join tables via BFS. Use innerFilters for WHERE and outerFilters for HAVING. Attribute Level Security (ALS) is applied at execution time based on the user\'s role. CRITICAL: When using aggregates, you MUST also include the aggregate column in selectedAttributes so it is available in the inner query.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'baseModel' => ['type' => 'string', 'description' => 'Root model short name (e.g. User, Order)'],
                            'targetModels' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Additional models to join. The engine auto-resolves joins via BFS.',
                            ],
                            'selectedAttributes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'model' => ['type' => 'string', 'description' => 'Short model name e.g. User'],
                                        'column' => ['type' => 'string', 'description' => 'Column name e.g. name, email'],
                                        'type' => ['type' => 'string', 'description' => 'Data type: string, integer, float, datetime, boolean, tuple'],
                                        'alias' => ['type' => 'string', 'description' => 'Optional output alias. CRITICAL if models share column names (e.g. User.name and Product.name)'],
                                        'isVirtual' => ['type' => 'boolean', 'description' => 'MUST be true if column is a virtual attribute (prefixed with va:)'],
                                    ],
                                    'required' => ['model', 'column'],
                                ],
                                'description' => 'Columns to SELECT. IMPORTANT: Must include ALL columns used in aggregates and sorts. Use alias to prevent collisions.',
                            ],
                            'innerFilters' => [
                                'type' => 'object',
                                'description' => 'WHERE clause. Use type:"leaf" with model, column, operator, value for a single condition. Use type:"group" with logic:"and"/"or" and children array for nested conditions. Filter leaf example: {"type":"leaf","model":"Order","column":"created_at","operator":">=","value":"2025-01-01"}',
                            ],
                            'groupBys' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'model' => ['type' => 'string'],
                                        'column' => ['type' => 'string'],
                                        'type' => ['type' => 'string'],
                                        'alias' => ['type' => 'string'],
                                        'isVirtual' => ['type' => 'boolean', 'description' => 'MUST be true if column is a virtual attribute'],
                                    ],
                                    'required' => ['model', 'column'],
                                ],
                                'description' => 'GROUP BY columns. Use flat format: {"model":"User","column":"name","type":"string","alias":"user_name"}',
                            ],
                            'aggregates' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'model' => ['type' => 'string', 'description' => 'Model that owns the column'],
                                        'column' => ['type' => 'string', 'description' => 'Column to aggregate'],
                                        'type' => ['type' => 'string'],
                                        'function' => ['type' => 'string', 'enum' => ['SUM', 'AVG', 'COUNT', 'MIN', 'MAX']],
                                        'alias' => ['type' => 'string', 'description' => 'Output alias for the aggregate'],
                                        'isVirtual' => ['type' => 'boolean', 'description' => 'MUST be true if column is a virtual attribute'],
                                    ],
                                    'required' => ['model', 'column', 'function', 'alias'],
                                ],
                                'description' => 'Aggregate functions. Use flat format: {"model":"Order","column":"total_amount","function":"SUM","alias":"total_sales"}',
                            ],
                            'outerFilters' => [
                                'type' => 'object',
                                'description' => 'HAVING clause. Same format as innerFilters, applied after GROUP BY.',
                            ],
                            'sorts' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'model' => ['type' => 'string'],
                                        'column' => ['type' => 'string'],
                                        'direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC']],
                                        'isVirtual' => ['type' => 'boolean', 'description' => 'MUST be true if column is a virtual attribute'],
                                    ],
                                    'required' => ['model', 'column', 'direction'],
                                ],
                                'description' => 'ORDER BY. Use flat format: {"model":"Order","column":"total_amount","direction":"DESC"}',
                            ],
                        ],
                        'required' => ['baseModel', 'selectedAttributes'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'debug_report_sql',
                    'description' => 'Compile a report AST to raw SQL without executing. Returns the fully-bound SQL string.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'payload' => ['type' => 'object', 'description' => 'Full ReportRequest AST payload'],
                        ],
                        'required' => ['payload'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'explain_join_plan',
                    'description' => 'Get the BFS join plan for a report. Shows how the engine resolves table relationships.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'payload' => ['type' => 'object', 'description' => 'Full ReportRequest AST payload'],
                        ],
                        'required' => ['payload'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'save_dynamic_report',
                    'description' => 'Save a report configuration to the library for later re-use.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Human-readable report name'],
                            'description' => ['type' => 'string', 'description' => 'What this report does'],
                            'payload' => ['type' => 'object', 'description' => 'The AST payload'],
                        ],
                        'required' => ['name', 'payload'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_saved_reports',
                    'description' => 'List all saved reports in the library.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'run_saved_report',
                    'description' => 'Execute a saved report by its ID and get the latest data results.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reportId' => ['type' => 'integer', 'description' => 'Saved report ID'],
                        ],
                        'required' => ['reportId'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_security_matrix',
                    'description' => 'Get the current Attribute Level Security (ALS) matrix for a model and role. Shows which columns are unrestricted, masked (***), or blocked (###).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'model_class' => ['type' => 'string', 'description' => 'Short model name'],
                            'role_id' => ['type' => 'integer', 'description' => 'Role ID'],
                        ],
                        'required' => ['model_class', 'role_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_audit_logs',
                    'description' => 'Query the report audit logs. Filter by action type, report ID, or user ID.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => ['type' => 'string', 'enum' => ['created', 'updated', 'executed', 'assigned', 'unassigned', 'deleted', 'error']],
                            'report_id' => ['type' => 'integer'],
                            'user_id' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'register_virtual_attribute',
                    'description' => 'Register a new virtual attribute (computed column) on a model using a raw SQL fragment. Use this when you need to group by a date part (e.g., month, year) or create a custom metric. CRITICAL: You MUST prefix physical column names with {THIS}. to prevent ambiguous column errors during JOINs! Example: to group by month in SQLite, register name="order_month" with sql_fragment="strftime(\'%Y-%m\', {THIS}.created_at)" on Order.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'model' => ['type' => 'string', 'description' => 'Short model name, e.g. Order'],
                            'name' => ['type' => 'string', 'description' => 'Name of the virtual attribute (without va: prefix). E.g., order_month'],
                            'sql_fragment' => ['type' => 'string', 'description' => 'Raw SQL fragment. E.g., strftime(\'%Y-%m\', created_at)'],
                            'dependencies' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Array of target model names this virtual attribute requires a JOIN to calculate. DO NOT list column names here. E.g. if it only uses columns from its base model, leave empty []. If it uses OrderItem, use ["OrderItem"]'
                            ]
                        ],
                        'required' => ['model', 'name', 'sql_fragment']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'assign_report',
                    'description' => 'Grant a specific user permission to view and execute a saved report. Uses the dynamic_report_user pivot table. The report owner always has access.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'report_id' => ['type' => 'integer', 'description' => 'The SavedReport ID'],
                            'user_id' => ['type' => 'integer', 'description' => 'The User ID to grant access to'],
                        ],
                        'required' => ['report_id', 'user_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'unassign_report',
                    'description' => 'Revoke a specific user\'s permission to view and execute a saved report. Does NOT affect the report owner.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'report_id' => ['type' => 'integer', 'description' => 'The SavedReport ID'],
                            'user_id' => ['type' => 'integer', 'description' => 'The User ID to revoke access from'],
                        ],
                        'required' => ['report_id', 'user_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_reports',
                    'description' => 'List only the saved reports that the current user owns or has been explicitly assigned access to.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ],
        ];
    }

    /**
     * Build the system prompt with schema context.
     */
    private function buildSystemPrompt(string $roleName): string
    {
        return <<<PROMPT
You are an AI assistant integrated with the Dynamic Report Generator engine via the Model Context Protocol (MCP). You help users query their business data through natural language.

CURRENT USER ROLE: {$roleName}

## Your Capabilities
You have access to MCP tools that let you:
1. **Discover** the database schema (models, attributes, relationships)
2. **Generate** dynamic reports by building AST payloads
3. **Debug** SQL and join plans
4. **Save/Load/Run** report configurations
5. **View** security matrices and audit logs

## Important Rules
1. ALWAYS discover the schema first before generating a report. Call get_available_models and get_model_attributes to understand what data is available.
2. When building a report AST, use short model names (e.g. "User", "Order", not full class paths).
3. The engine auto-joins tables using BFS graph traversal — you only need to specify targetModels.
4. Virtual attributes are prefixed with "va:" in the attribute list. Set isVirtual: true when using them.
5. Attribute Level Security (ALS) is automatically enforced. Masked columns show "***" in results. Blocked columns throw errors. You don't control this — just inform the user when masking is visible.
6. Allowed filter operators: =, !=, >, >=, <, <=, like, in, between, is null, is not null
7. Allowed aggregate functions: SUM, AVG, COUNT, MIN, MAX
8. Keep responses concise but informative. **ALWAYS format data results using Markdown tables in your final reply**. Include bullet points for key insights.
9. When data shows "***" values, explain that this is due to Attribute Level Security masking for the current role.
10. If you need to group by a date part (like month or year), use the register_virtual_attribute tool FIRST to create a computed column (e.g., strftime('%Y-%m', {THIS}.created_at)), then use that new virtual attribute (prefix with va:) in your report payload.
11. **CRITICAL COLLISION RULE:** If you select columns with the exact same name from different models (e.g. User.name and Product.name), you MUST provide a unique `alias` for them in BOTH `selectedAttributes` and `groupBys`. Otherwise, the results will overwrite each other.

## AST Payload Format (CRITICAL)
All arrays in the AST use a FLAT format with "model" and "column" at root level. Do NOT nest them inside an "attribute" object.

### selectedAttributes format:
```json
[{"model": "User", "column": "name", "type": "string", "alias": "salesperson_name"}, {"model": "Order", "column": "va:order_month", "type": "string", "isVirtual": true}]
```
*Note: Virtual attributes can also return integers, floats, or tuples depending on your sql_fragment.*

### aggregates format:
```json
[{"model": "Order", "column": "total_amount", "type": "float", "function": "SUM", "alias": "total_sales"}]
```

### groupBys format:
```json
[{"model": "User", "column": "name", "type": "string", "alias": "salesperson_name"}]
```

### sorts format:
```json
[{"model": "Order", "column": "total_amount", "direction": "DESC"}]
```

### Filter leaf format:
```json
{"type": "leaf", "model": "Order", "column": "created_at", "operator": ">=", "value": "2025-01-01"}
```

### Filter group format:
```json
{"type": "group", "logic": "and", "children": [{"type": "leaf", "model": "Order", "column": "status", "operator": "=", "value": "completed"}]}
```

## CRITICAL: Aggregation Rules
When using aggregates:
1. You MUST include the aggregate column in selectedAttributes too (the engine needs it in the inner SELECT).
2. You MUST include groupBy columns in selectedAttributes.
3. Example: "total sales per user" requires:
   - selectedAttributes: [{"model":"User","column":"name"}, {"model":"Order","column":"total_amount"}]
   - aggregates: [{"model":"Order","column":"total_amount","function":"SUM","alias":"total_sales"}]
   - groupBys: [{"model":"User","column":"name"}]
   - targetModels: ["Order"]
PROMPT;
    }

    /**
     * POST /api/mcp/chat
     *
     * Main orchestration endpoint. Processes natural language through DeepSeek
     * with function calling, executes MCP tools, and returns the response.
     */
    public function chat(Request $request): JsonResponse
    {
        $this->apiKey = config('services.deepseek.api_key', env('DEEPSEEK_API_KEY', ''));

        if (empty($this->apiKey)) {
            return response()->json(['error' => 'DeepSeek API key not configured'], 500);
        }

        $userMessage = $request->input('message', '');
        $roleId = $request->input('role_id');
        $conversationHistory = $request->input('history', []);

        // Resolve role name
        $role = $roleId ? \App\Models\Role::find($roleId) : null;
        $roleName = $role ? $role->name : 'Admin (unrestricted)';

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($roleName)],
        ];

        // Append conversation history (last 10 messages), filtering out 'status' UI messages
        foreach (array_slice($conversationHistory, -10) as $msg) {
            if (in_array($msg['role'], ['user', 'assistant'])) {
                $messages[] = $msg;
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $toolsUsed = [];
        $reportData = null;
        $reportSql = null;
        $reportAst = null;
        $maskingApplied = [];

        // Agentic loop: keep calling DeepSeek until it stops requesting tools
        $maxIterations = 15;
        for ($i = 0; $i < $maxIterations; $i++) {
            $response = $this->callDeepSeek($messages);

            if (!$response) {
                return response()->json(['error' => 'Failed to call DeepSeek API'], 500);
            }

            $choice = $response['choices'][0] ?? null;
            if (!$choice) {
                return response()->json(['error' => 'Empty response from DeepSeek'], 500);
            }

            $assistantMessage = $choice['message'];
            $messages[] = $assistantMessage;

            // Check if the model wants to call tools
            if (($choice['finish_reason'] === 'tool_calls' || !empty($assistantMessage['tool_calls'])) && !empty($assistantMessage['tool_calls'])) {
                foreach ($assistantMessage['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'];
                    $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];

                    Log::info("MCP Tool Call: {$toolName}", $toolArgs);

                    // Execute the tool
                    $toolResult = $this->executeTool($toolName, $toolArgs, $roleId);

                    $toolsUsed[] = [
                        'name' => $toolName,
                        'args' => $toolArgs,
                        'success' => !isset($toolResult['error']),
                    ];

                    // Track report-specific data for the frontend
                    if ($toolName === 'generate_dynamic_report') {
                        $reportData = $toolResult['data'] ?? null;
                        $reportSql = $toolResult['sql'] ?? null;
                        $reportAst = $toolArgs;

                        // Detect masking
                        if ($reportData && is_array($reportData)) {
                            foreach ($reportData as $row) {
                                $rowArr = (array)$row;
                                foreach ($rowArr as $col => $val) {
                                    if ($val === '***') {
                                        $maskingApplied[$col] = 'masked';
                                    } elseif ($val === '###') {
                                        $maskingApplied[$col] = 'blocked';
                                    }
                                }
                            }
                        }
                    }

                    if ($toolName === 'debug_report_sql') {
                        $reportSql = $toolResult['sql'] ?? null;
                    }

                    if ($toolName === 'run_saved_report') {
                        $reportData = $toolResult['data'] ?? null;
                        $reportSql = $toolResult['sql'] ?? null;
                    }

                    if (in_array($toolName, ['get_saved_reports', 'get_my_reports'])) {
                        $reportData = $toolResult['reports'] ?? null;
                    }

                    // Feed result back to the model
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($toolResult),
                    ];
                }
            } else {
                // Model finished — return the final text response
                $reply = $assistantMessage['content'] ?? 'No response generated.';

                return response()->json([
                    'reply' => $reply,
                    'data' => $reportData,
                    'sql' => $reportSql,
                    'ast' => $reportAst,
                    'tools_used' => $toolsUsed,
                    'masking_applied' => $maskingApplied,
                    'role' => $roleName,
                ]);
            }
        }

        // If we exhausted iterations, try to find the last assistant message text
        $lastReply = 'I needed too many steps to complete this request. Please be more specific.';
        foreach (array_reverse($messages) as $msg) {
            if ($msg['role'] === 'assistant' && !empty($msg['content'])) {
                $lastReply = $msg['content'];
                break;
            }
        }

        return response()->json([
            'reply' => $lastReply,
            'data' => $reportData,
            'sql' => $reportSql,
            'ast' => $reportAst,
            'tools_used' => $toolsUsed,
            'masking_applied' => $maskingApplied,
            'role' => $roleName,
        ]);
    }

    /**
     * Call the DeepSeek API with function-calling support.
     */
    private function callDeepSeek(array $messages): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $this->getToolDefinitions(),
                'tool_choice' => 'auto',
                'temperature' => 0.1,
                'max_tokens' => 4096,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('DeepSeek API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('DeepSeek API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute an MCP tool by name, routing to the appropriate McpController method.
     */
    private function executeTool(string $toolName, array $args, ?string $roleId): array
    {
        $mcpController = app(McpController::class);

        try {
            switch ($toolName) {
                case 'get_available_models':
                    $response = $mcpController->getAvailableModels();
                    return $response->getData(true);

                case 'get_model_attributes':
                    $response = $mcpController->getModelAttributes($args['model'] ?? '');
                    return $response->getData(true);

                case 'get_model_relationships':
                    $response = $mcpController->getModelRelationships($args['model'] ?? '');
                    return $response->getData(true);

                case 'get_max_filter_depth':
                    $response = $mcpController->getMaxFilterDepth();
                    return $response->getData(true);

                case 'generate_dynamic_report':
                    $request = new Request($args);
                    $request->headers->set('X-Role-Id', $roleId);
                    $response = $mcpController->generateReport($request);
                    return $response->getData(true);

                case 'debug_report_sql':
                    $payload = $args['payload'] ?? $args;
                    $request = new Request($payload);
                    $request->headers->set('X-Role-Id', $roleId);
                    $response = $mcpController->debugSql($request);
                    return $response->getData(true);

                case 'explain_join_plan':
                    $payload = $args['payload'] ?? $args;
                    $request = new Request($payload);
                    $response = $mcpController->explainJoinPlan($request);
                    return $response->getData(true);

                case 'save_dynamic_report':
                    $request = new Request($args);
                    $response = $mcpController->saveReport($request);
                    return $response->getData(true);

                case 'get_saved_reports':
                    $response = $mcpController->getSavedReports();
                    return $response->getData(true);

                case 'run_saved_report':
                    $request = new Request($args);
                    $response = $mcpController->runSavedReport($request, $args['reportId'] ?? 0);
                    return $response->getData(true);

                case 'assign_report':
                    $request = new Request($args);
                    $response = $mcpController->assignReport($request);
                    return $response->getData(true);

                case 'unassign_report':
                    $request = new Request($args);
                    $response = $mcpController->unassignReport($request);
                    return $response->getData(true);

                case 'get_my_reports':
                    $request = new Request($args);
                    $response = $mcpController->getMyReports($request);
                    return $response->getData(true);

                case 'get_security_matrix':
                    $request = new Request($args);
                    $response = $mcpController->getSecurityMatrix($request);
                    return $response->getData(true);

                case 'get_audit_logs':
                    $request = new Request($args);
                    $response = $mcpController->getAuditLogs($request);
                    return $response->getData(true);

                case 'register_virtual_attribute':
                    $request = new Request($args);
                    $response = $mcpController->registerVirtualAttribute($request);
                    return $response->getData(true);

                default:
                    return ['error' => "Unknown tool: {$toolName}"];
            }
        } catch (\Throwable $e) {
            Log::error("MCP Tool execution error [{$toolName}]: " . $e->getMessage());
            return ['error' => $e->getMessage(), 'type' => class_basename($e)];
        }
    }
}
