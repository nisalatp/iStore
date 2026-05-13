<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;
use Nisalatp\DynamicReportGenerator\Models\AttributeRestriction;
use App\Models\Role;
use App\Models\User;

class SecurityConfigController extends Controller
{
    public function index()
    {
        $allModels = DynamicReport::getAllApplicationModels();
        $roles = Role::all();
        $users = User::all();

        return view('admin.security', [
            'models' => $allModels,
            'roles' => $roles,
            'users' => $users
        ]);
    }

    public function getMatrix(Request $request)
    {
        $modelClass = $request->query('model_class');
        $subjectType = $request->query('subject_type'); // 'User' or 'Role'
        $subjectId = $request->query('subject_id');
        
        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['error' => 'Invalid model class'], 400);
        }

        if (!in_array($subjectType, ['User', 'Role']) || !$subjectId) {
            return response()->json(['error' => 'Invalid subject'], 400);
        }

        $subjectClass = $subjectType === 'User' ? User::class : Role::class;

        try {
            $matrixData = \Nisalatp\DynamicReportGenerator\Services\GovernanceManager::getMatrix($modelClass, $subjectClass, $subjectId);
            return response()->json($matrixData);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        $request->validate([
            'model_class' => 'required|string',
            'subject_type' => 'required|string|in:User,Role',
            'subject_id' => 'required|integer',
            'is_reportable' => 'required|boolean',
            'attributes' => 'required|array'
        ]);

        $modelClass = $request->input('model_class');
        $subjectType = $request->input('subject_type') === 'User' ? User::class : Role::class;
        $subjectId = $request->input('subject_id');
        $isReportable = $request->input('is_reportable');
        $attributes = $request->input('attributes');

        try {
            \Nisalatp\DynamicReportGenerator\Services\GovernanceManager::saveMatrix(
                $modelClass,
                $subjectClass,
                $subjectId,
                $isReportable,
                $attributes,
                auth()->id() ?? 1
            );
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
