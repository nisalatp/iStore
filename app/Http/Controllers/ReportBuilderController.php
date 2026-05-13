<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Types\Aggregate;
use Nisalatp\DynamicReportGenerator\Types\Attribute;
use Nisalatp\DynamicReportGenerator\Types\GroupBy;
use Nisalatp\DynamicReportGenerator\Types\ReportRequest;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;
use Nisalatp\DynamicReportGenerator\Types\FilterLeaf;
use Nisalatp\DynamicReportGenerator\Types\FilterGroup;
use Illuminate\Support\Facades\Schema;

class ReportBuilderController extends Controller
{
    public function index()
    {
        // Broker the discovery through the Report Generator engine instead of manual reflection
        $models = DynamicReport::getAvailableModels();
        
        $modelOptions = collect($models)->map(function ($modelClass) {
            $attributes = DynamicReport::getModelAttributes($modelClass);

            return [
                'class' => $modelClass,
                'name' => class_basename($modelClass),
                'columns' => $attributes
            ];
        })->values();

        return view('admin.report_builder', compact('modelOptions'));
    }

    public function generate(Request $request)
    {
        try {
            $payload = $request->validate([
                'baseModel' => 'required|string',
                'targetModels' => 'array',
                'selectedAttributes' => 'array',
                'innerFilters' => 'nullable|array',
                'groupBys' => 'array',
                'aggregates' => 'array',
                'outerFilters' => 'nullable|array',
                'sorts' => 'array',
            ]);

            $reportRequest = \Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest::fromPayload($payload);

            $query = DynamicReport::generate($reportRequest);
            $rawSql = DynamicReport::toRawSql($query);
            $paginator = DynamicReport::generatePaginated($reportRequest, 50);
            $joinPlan = DynamicReport::explainJoinPlan($reportRequest);

            return response()->json([
                'success' => true,
                'sql' => $rawSql,
                'join_plan' => $joinPlan->steps,
                'results' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function simulateColumns(Request $request)
    {
        try {
            $payload = $request->validate([
                'baseModel' => 'required|string',
                'targetModels' => 'array',
                'selectedAttributes' => 'array',
                'groupBys' => 'array',
                'aggregates' => 'array',
            ]);

            $reportRequest = \Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest::fromPayload($payload);
            $columns = DynamicReport::getGeneratedColumns($reportRequest);

            return response()->json([
                'success' => true,
                'columns' => $columns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $payload = $request->validate([
                'baseModel' => 'required|string',
                'targetModels' => 'array',
                'selectedAttributes' => 'array',
                'innerFilters' => 'nullable|array',
                'groupBys' => 'array',
                'aggregates' => 'array',
                'outerFilters' => 'nullable|array',
                'sorts' => 'array',
            ]);

            $reportRequest = \Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest::fromPayload($payload);

            return DynamicReport::exportToCsv($reportRequest, 'dynamic_report_export.csv');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $payload = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'payload' => 'required|array'
            ]);

            $reportRequest = \Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest::fromPayload($payload['payload']);
            
            $savedReport = DynamicReport::saveReport(
                $payload['name'], 
                $reportRequest, 
                null, // No Auth user in demo app
                $payload['description'] ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Report saved successfully!',
                'report' => $savedReport
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSaved()
    {
        $reports = DynamicReport::getSavedReports();
        return response()->json(['success' => true, 'reports' => $reports]);
    }

    public function load($id)
    {
        try {
            // We need to return the raw payload to the frontend so it can rebuild the UI
            $savedReport = \Nisalatp\DynamicReportGenerator\Models\SavedReport::findOrFail($id);
            $payload = is_string($savedReport->payload) ? json_decode($savedReport->payload, true) : $savedReport->payload;
            
            return response()->json([
                'success' => true,
                'payload' => $payload
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function executeSaved($id)
    {
        try {
            $query = DynamicReport::loadAndGenerate($id);
            $rawSql = DynamicReport::toRawSql($query);
            
            // To get join plan for saved report, we must re-parse it
            $savedReport = \Nisalatp\DynamicReportGenerator\Models\SavedReport::findOrFail($id);
            $payload = is_string($savedReport->payload) ? json_decode($savedReport->payload, true) : $savedReport->payload;
            $reportRequest = \Nisalatp\DynamicReportGenerator\Types\ReportRequest::fromJson(json_encode($payload));
            $joinPlan = DynamicReport::explainJoinPlan($reportRequest);
            $paginator = $query->paginate(50);

            return response()->json([
                'success' => true,
                'sql' => $rawSql,
                'join_plan' => $joinPlan->steps,
                'results' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Backend parsing logic has been offloaded to Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest
}
