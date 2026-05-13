<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Nisalatp\DynamicReportGenerator\Models\RestrictedModel;

echo "\n═══ Running Migration ═══\n";
Artisan::call('migrate');
echo Artisan::output();

// Clean up before test
RestrictedModel::truncate();

echo "\n═══ Test 1: getAllApplicationModels() ═══\n";
$allModels = DynamicReport::getAllApplicationModels();
$hasUser = in_array('App\Models\User', $allModels);
$hasOrder = in_array('App\Models\Order', $allModels);

echo "Total models found: " . count($allModels) . "\n";
if ($hasUser && $hasOrder) {
    echo "  ✅ PASS: Discovered standard App\Models (User, Order)\n";
} else {
    echo "  ❌ FAIL: Missing standard App\Models\n";
}

echo "\n═══ Test 2: Default getAvailableModels() ═══\n";
$available = DynamicReport::getAvailableModels();
if (count($available) === count($allModels)) {
    echo "  ✅ PASS: Available models matches all models (no restrictions yet)\n";
} else {
    echo "  ❌ FAIL: Available models do not match all models\n";
}

echo "\n═══ Test 3: Restrict a Model ═══\n";
DynamicReport::restrictModel('App\Models\User');
$restricted = DynamicReport::getRestrictedModels();

if (in_array('App\Models\User', $restricted)) {
    echo "  ✅ PASS: User model is now in the restricted models list\n";
} else {
    echo "  ❌ FAIL: User model not found in restricted models\n";
}

$availableNow = DynamicReport::getAvailableModels();
if (!in_array('App\Models\User', $availableNow)) {
    echo "  ✅ PASS: User model has been successfully excluded from available models\n";
} else {
    echo "  ❌ FAIL: User model is still present in available models\n";
}

echo "\n═══ Test 4: Unrestrict a Model ═══\n";
DynamicReport::unrestrictModel('App\Models\User');
$restrictedNow = DynamicReport::getRestrictedModels();

if (!in_array('App\Models\User', $restrictedNow)) {
    echo "  ✅ PASS: User model removed from restricted models list\n";
} else {
    echo "  ❌ FAIL: User model is still in restricted models\n";
}

$availableRestored = DynamicReport::getAvailableModels();
if (in_array('App\Models\User', $availableRestored)) {
    echo "  ✅ PASS: User model restored to available models\n";
} else {
    echo "  ❌ FAIL: User model missing from available models after un-restriction\n";
}

echo "\n═══════════════════════════════════════════\n";
echo "ALL TESTS COMPLETE\n";
echo "═══════════════════════════════════════════\n";
