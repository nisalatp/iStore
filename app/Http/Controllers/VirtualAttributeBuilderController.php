<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Nisalatp\DynamicReportGenerator\Builders\VirtualAttributeBuilder;

class VirtualAttributeBuilderController extends Controller
{
    public function index()
    {
        $models = \Nisalatp\DynamicReportGenerator\Facades\DynamicReport::getAvailableModels();

        $modelOptions = collect($models)->map(function ($modelClass) {
            $table = (new $modelClass)->getTable();
            // Get physical and virtual columns to allow dependencies
            $columns = \Nisalatp\DynamicReportGenerator\Facades\DynamicReport::getModelAttributes($modelClass);
            return [
                'class' => $modelClass,
                'name' => class_basename($modelClass),
                'table' => $table,
                'columns' => $columns
            ];
        })->values();

        // Fetch existing VAs to display them in a list
        $existingVAs = \Nisalatp\DynamicReportGenerator\Models\VirtualAttribute::all();

        return view('admin.virtual_attributes', compact('modelOptions', 'existingVAs'));
    }

    public function register(Request $request)
    {
        try {
            $payload = $request->validate([
                'name' => 'required|string|max:255',
                'baseModel' => 'required|string',
                'sqlFragment' => 'nullable|string',
                'dependencies' => 'array',
                'builderMode' => 'nullable|string',
                'ast' => 'nullable|array'
            ]);

            $sqlFragment = $payload['sqlFragment'] ?? '';
            $dependencies = $payload['dependencies'] ?? [];

            // Compile AST to SQL if visual builder mode is used
            if (($payload['builderMode'] ?? 'sql') === 'visual' && !empty($payload['ast'])) {
                $ast = $payload['ast'];

                $sqlFragment = \Nisalatp\DynamicReportGenerator\Services\VirtualAttributeCompiler::compileVisualPayload($payload);

                // Save the AST inside dependencies so it can be reloaded later
                $dependencies['_ast'] = $ast;
            }

            // Register the VA using the fluent API
            $builder = VirtualAttributeBuilder::create($payload['name'])
                ->forBaseModel($payload['baseModel'])
                ->withSqlFragment($sqlFragment);

            if (!empty($dependencies)) {
                $builder->dependsOn($dependencies);
            }

            $builder->register();

            return response()->json([
                'success' => true,
                'message' => 'Virtual Attribute "' . $payload['name'] . '" registered successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function compile(Request $request)
    {
        try {
            $payload = $request->validate([
                'baseModel' => 'required|string',
                'dependencies' => 'array',
                'ast' => 'required|array'
            ]);

            $sqlFragment = \Nisalatp\DynamicReportGenerator\Services\VirtualAttributeCompiler::compileVisualPayload($payload);

            return response()->json([
                'success' => true,
                'sql' => $sqlFragment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
