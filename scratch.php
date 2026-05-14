<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Nisalatp\DynamicReportGenerator\Http\Requests\ReportBuilderRequest;
use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;

$payloadStr = '{"baseModel":"Order","selectedAttributes":[{"model":"Order","column":"id","type":"integer"},{"model":"Order","column":"total_amount","type":"float"},{"model":"Order","column":"status","type":"string"},{"model":"Order","column":"created_at","type":"datetime"},{"model":"Order","column":"va:weekday_name","type":"string","isVirtual":true}],"innerFilters":{"type":"group","logic":"and","children":[{"type":"leaf","model":"Order","column":"va:weekday_name","operator":"!=","value":"Saturday","isVirtual":true},{"type":"leaf","model":"Order","column":"va:weekday_name","operator":"!=","value":"Sunday","isVirtual":true}]}}';
$payload = json_decode($payloadStr, true);

$controller = app(\App\Http\Controllers\McpController::class);
$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('normalizePayload');
$method->setAccessible(true);
$payload = $method->invokeArgs($controller, [$payload]);

$expandMethod = $reflection->getMethod('expandModelClasses');
$expandMethod->setAccessible(true);
$payload = $expandMethod->invokeArgs($controller, [$payload]);

try {
    $reportRequest = ReportBuilderRequest::fromPayload($payload);
    $query = DynamicReport::generate($reportRequest, []);
    $results = $query->get();
    echo "SUCCESS\n";
    print_r($results->toArray());
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
