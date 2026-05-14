<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DemoController;
use App\Http\Controllers\ReportBuilderController;
use App\Http\Controllers\VirtualAttributeBuilderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\McpAgentController;

// Storefront Route
Route::get('/', function () {
    return view('storefront.index');
})->name('storefront.index');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes (Protected)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/report-builder', [ReportBuilderController::class, 'index'])->name('report_builder');

    Route::get('/virtual-attributes', [VirtualAttributeBuilderController::class, 'index'])->name('virtual_attributes');
    Route::get('/virtual-attributes-list', [App\Http\Controllers\VirtualAttributeManagerController::class, 'index'])->name('virtual_attributes.list');
    Route::delete('/virtual-attributes/{id}', [App\Http\Controllers\VirtualAttributeManagerController::class, 'destroy'])->name('virtual_attributes.destroy');

    Route::get('/models', [App\Http\Controllers\ModelConfigController::class, 'index'])->name('models');
    Route::post('/models/toggle', [App\Http\Controllers\ModelConfigController::class, 'toggleRestriction'])->name('models.toggle');

    Route::get('/security', [App\Http\Controllers\SecurityConfigController::class, 'index'])->name('security');
    Route::get('/security/matrix', [App\Http\Controllers\SecurityConfigController::class, 'getMatrix'])->name('security.matrix');
    Route::post('/security/save', [App\Http\Controllers\SecurityConfigController::class, 'save'])->name('security.save');
    
    Route::get('/reports', [App\Http\Controllers\SavedReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{id}/assign', [App\Http\Controllers\SavedReportController::class, 'assign'])->name('reports.assign');

    Route::get('/mcp-agent', [McpAgentController::class, 'index'])->name('mcp_agent');
    Route::post('/mcp-agent/chat', [McpAgentController::class, 'chat'])->name('mcp_agent.chat');
});

// Old API Routes
Route::get('/demo-old', [DemoController::class, 'index'])->name('demo.index');

Route::get('/builder', [ReportBuilderController::class, 'index'])->name('builder.index');
Route::post('/builder/generate', [ReportBuilderController::class, 'generate'])->name('builder.generate');
Route::post('/builder/simulate-columns', [ReportBuilderController::class, 'simulateColumns'])->name('builder.simulate_columns');
Route::post('/builder/save', [ReportBuilderController::class, 'save'])->name('builder.save');
Route::post('/builder/export', [ReportBuilderController::class, 'export'])->name('builder.export');
Route::get('/builder/saved', [ReportBuilderController::class, 'getSaved'])->name('builder.get_saved');
Route::get('/builder/saved/{id}/load', [ReportBuilderController::class, 'load'])->name('builder.load_saved');
Route::post('/builder/saved/{id}/execute', [ReportBuilderController::class, 'executeSaved'])->name('builder.execute_saved');

Route::get('/va-builder', [VirtualAttributeBuilderController::class, 'index'])->name('va_builder.index');
Route::post('/va-builder/compile', [VirtualAttributeBuilderController::class, 'compile'])->name('va_builder.compile');
Route::post('/va-builder/register', [VirtualAttributeBuilderController::class, 'register'])->name('va_builder.register');

