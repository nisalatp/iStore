<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Nisalatp\DynamicReportGenerator\Facades\DynamicReport;
use Nisalatp\DynamicReportGenerator\Types\ReportRequest;
use Nisalatp\DynamicReportGenerator\Types\Attribute;
use Nisalatp\DynamicReportGenerator\Types\FilterNode;
use Nisalatp\DynamicReportGenerator\Types\FilterLeaf;
use Nisalatp\DynamicReportGenerator\Exceptions\ReportMakerSecurityException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Model;

// Mock Subject Model
class TestRole extends Model {
    protected $table = 'roles';
    public $id = 1;
    public function getKey() { return 1; }
}

echo "\n═══ Running Migration ═══\n";
Artisan::call('migrate');
echo Artisan::output();

$subject = new TestRole();

echo "\n═══ Resetting Rules ═══\n";
DynamicReport::unrestrictAttribute('App\Models\User', 'email', $subject);
DynamicReport::unrestrictAttribute('App\Models\User', 'password', $subject);

echo "\n═══ Test 1: Masked Attribute ═══\n";
DynamicReport::restrictAttribute('App\Models\User', 'email', $subject, 'masked');

$req1 = new ReportRequest(
    baseModel: 'App\Models\User',
    targetModels: [],
    selectedAttributes: [
        new Attribute('App\Models\User', 'id', 'integer'),
        new Attribute('App\Models\User', 'email', 'string')
    ]
);

$sql1 = DynamicReport::generate($req1, [$subject])->toSql();

if (str_contains($sql1, "'***' as \"email\"")) {
    echo "  ✅ PASS: Email is masked in the output.\n";
} else {
    echo "  ❌ FAIL: Email was not masked.\n$sql1\n";
}

echo "\n═══ Test 2: Masked Attribute Allowed in Backend ═══\n";
$req2 = new ReportRequest(
    baseModel: 'App\Models\User',
    targetModels: [],
    selectedAttributes: [
        new Attribute('App\Models\User', 'id', 'integer'),
        new Attribute('App\Models\User', 'email', 'string')
    ],
    innerFilters: new FilterLeaf(new Attribute('App\Models\User', 'email', 'string'), '=', 'test@test.com')
);

try {
    $sql2 = DynamicReport::generate($req2, [$subject])->toSql();
    if (str_contains($sql2, "'***' as \"email\"") && str_contains($sql2, '"email" = ?')) {
        echo "  ✅ PASS: Masked attribute successfully allowed in backend filters.\n";
    } else {
        echo "  ❌ FAIL: Missing filter logic.\n$sql2\n";
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Should not throw exception for masked attribute in filter.\n";
}

echo "\n═══ Test 3: Blocked Attribute Output ═══\n";
DynamicReport::restrictAttribute('App\Models\User', 'password', $subject, 'blocked');

$req3 = new ReportRequest(
    baseModel: 'App\Models\User',
    targetModels: [],
    selectedAttributes: [
        new Attribute('App\Models\User', 'id', 'integer'),
        new Attribute('App\Models\User', 'password', 'string')
    ]
);

$sql3 = DynamicReport::generate($req3, [$subject])->toSql();
if (str_contains($sql3, "'###' as \"password\"")) {
    echo "  ✅ PASS: Password is blocked and output is replaced with ###.\n";
} else {
    echo "  ❌ FAIL: Password was not replaced with ###.\n$sql3\n";
}

echo "\n═══ Test 4: Blocked Attribute in Filter Throws Exception ═══\n";
$req4 = new ReportRequest(
    baseModel: 'App\Models\User',
    targetModels: [],
    selectedAttributes: [
        new Attribute('App\Models\User', 'id', 'integer'),
        new Attribute('App\Models\User', 'password', 'string')
    ],
    innerFilters: new FilterLeaf(new Attribute('App\Models\User', 'password', 'string'), '=', 'secret')
);

try {
    DynamicReport::generate($req4, [$subject]);
    echo "  ❌ FAIL: Security Exception was not thrown when using blocked attribute in filter.\n";
} catch (ReportMakerSecurityException $e) {
    echo "  ✅ PASS: Exception correctly thrown: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════\n";
echo "ALL TESTS COMPLETE\n";
echo "═══════════════════════════════════════════\n";
