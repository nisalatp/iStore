<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Models\VirtualAttribute;
use Nisalatp\DynamicReportGenerator\Models\SavedReport;

class VirtualAttributeManagerController extends Controller
{
    /**
     * Display a listing of all Virtual Attributes with their usage count.
     */
    public function index()
    {
        $virtualAttributes = \Nisalatp\DynamicReportGenerator\Services\VirtualAttributeManager::getAllWithUsageCounts();
        return view('admin.virtual_attributes_list', compact('virtualAttributes'));
    }

    /**
     * Delete a Virtual Attribute.
     */
    public function destroy($id, Request $request)
    {
        try {
            \Nisalatp\DynamicReportGenerator\Services\VirtualAttributeManager::safeDelete($id, $request->boolean('force'));

            return response()->json([
                'success' => true,
                'message' => 'Virtual Attribute deleted successfully.'
            ]);
        } catch (\Exception $e) {
            $isConflict = str_contains($e->getMessage(), 'force delete');
            return response()->json([
                'success' => false,
                'requires_force' => $isConflict,
                'message' => $isConflict ? $e->getMessage() : 'Error deleting Virtual Attribute: ' . $e->getMessage()
            ], $isConflict ? 409 : 500);
        }
    }
}
