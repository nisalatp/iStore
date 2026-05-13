<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Models\SavedReport;
use App\Models\User;
use App\Models\Role;

class SavedReportController extends Controller
{
    public function index()
    {
        $reports = SavedReport::with('assignedUsers')->get();
        $users = User::all();
        $roles = Role::all();
        
        return view('admin.reports', compact('reports', 'users', 'roles'));
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $report = SavedReport::findOrFail($id);
        
        // Save role IDs in the JSON payload
        $payload = $report->payload ?? [];
        $payload['assigned_roles'] = $request->role_ids ?? [];
        $report->payload = $payload;
        $report->save();
        
        // Sync the user IDs to the dynamic_report_user pivot table
        $report->assignedUsers()->sync($request->user_ids ?? []);

        return redirect()->back()->with('success', 'Assignments successfully updated.');
    }
}
