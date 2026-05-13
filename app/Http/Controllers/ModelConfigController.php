<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;

class ModelConfigController extends Controller
{
    public function index()
    {
        $allModels = DynamicReport::getAllApplicationModels();
        $restrictedModels = DynamicReport::getRestrictedModels();

        // Prepare data for the frontend
        $modelsData = [];
        foreach ($allModels as $modelClass) {
            $isRestricted = in_array($modelClass, $restrictedModels);
            
            // Get some basic stats for the UI
            try {
                $attributes = \Nisalatp\DynamicReportGenerator\Facades\DynamicReport::getModelAttributes($modelClass);
                $columnsCount = count($attributes);
            } catch (\Exception $e) {
                $columnsCount = 0;
            }

            $modelsData[] = [
                'class' => $modelClass,
                'name' => class_basename($modelClass),
                'is_restricted' => $isRestricted,
                'columns_count' => $columnsCount,
                'description' => 'System model for ' . class_basename($modelClass) . ' data.'
            ];
        }

        return view('admin.models', ['models' => $modelsData]);
    }

    public function toggleRestriction(Request $request)
    {
        $request->validate([
            'model_class' => 'required|string',
            'is_restricted' => 'required|boolean'
        ]);

        $modelClass = $request->input('model_class');
        $isRestricted = $request->input('is_restricted');

        try {
            if ($isRestricted) {
                // If the frontend says it should be restricted, we restrict it
                DynamicReport::restrictModel($modelClass);
            } else {
                DynamicReport::unrestrictModel($modelClass);
            }

            return response()->json([
                'success' => true,
                'message' => 'Model ' . class_basename($modelClass) . ' ' . ($isRestricted ? 'restricted' : 'unrestricted') . ' successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
