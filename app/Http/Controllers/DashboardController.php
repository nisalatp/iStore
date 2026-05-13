<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;

class DashboardController extends Controller
{
    public function index()
    {
        $savedReports = DynamicReport::getSavedReports();

        return view('admin.dashboard', [
            'reports' => $savedReports
        ]);
    }
}
