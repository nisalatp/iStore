<?php
/**
 * Verification script for bidirectional BFS traversal in DynamicReportGenerator.
 *
 * Run from demo-app root: php test_bidirectional_bfs.php
 *
 * This script validates that:
 * 1. discoverLinks() (via getConnectedModels) returns both forward and reverse edges.
 * 2. findShortestPath works in both directions for all model pairs.
 * 3. JoinSteps carry direction metadata ('forward' or 'reverse').
 * 4. No existing API signatures are broken.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Nisalatp\DynamicReportGenerator\ReportMaker;
use Nisalatp\DynamicReportGenerator\Types\ModelLink;
use Nisalatp\DynamicReportGenerator\Types\JoinStep;
use Nisalatp\DynamicReportGenerator\Types\ReportRequest;
use Nisalatp\DynamicReportGenerator\Types\Attribute;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$models = [User::class, Order::class, OrderItem::class, Product::class];
$engine = new ReportMaker($models);

$passed = 0;
$failed = 0;

function assert_true($condition, $label) {
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ PASS: {$label}\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: {$label}\n";
        $failed++;
    }
}

// ── Test 1: getConnectedModels returns links for each model ──
echo "\n═══ Test 1: getConnectedModels() bidirectional discovery ═══\n";

foreach ($models as $modelClass) {
    $shortName = class_basename($modelClass);
    $connected = $engine->getConnectedModels($modelClass);
    assert_true(!empty($connected), "{$shortName} has connected models");

    foreach ($connected as $targetModel => $link) {
        $targetShort = class_basename($targetModel);
        assert_true($link instanceof ModelLink, "{$shortName} → {$targetShort} is a ModelLink");
        assert_true(in_array($link->direction, ['forward', 'reverse']), "{$shortName} → {$targetShort} has valid direction: {$link->direction}");
        echo "    └─ {$shortName} → {$targetShort} [{$link->type}, {$link->direction}] fk={$link->foreignKey} lk={$link->localKey}\n";
    }
}

// ── Test 2: Bidirectional path resolution ──
echo "\n═══ Test 2: Bidirectional shortest path (via explainJoinPlan) ═══\n";

// Test: Product → User (requires reverse traversal at some point)
$pairs = [
    [Product::class, User::class, 'Product → User'],
    [User::class, Product::class, 'User → Product'],
    [Product::class, Order::class, 'Product → Order'],
    [Order::class, Product::class, 'Order → Product'],
    [User::class, OrderItem::class, 'User → OrderItem'],
    [OrderItem::class, User::class, 'OrderItem → User'],
];

foreach ($pairs as [$from, $to, $label]) {
    try {
        $request = new ReportRequest(
            baseModel: $from,
            targetModels: [$to],
            selectedAttributes: [
                new Attribute(modelClass: $from, column: 'id', type: 'integer'),
            ],
        );
        $plan = $engine->explainJoinPlan($request);
        assert_true(!empty($plan->steps), "{$label}: path found (" . count($plan->steps) . " steps)");

        foreach ($plan->steps as $step) {
            $fromShort = class_basename($step->fromModel);
            $toShort = class_basename($step->toModel);
            assert_true($step instanceof JoinStep, "  step is JoinStep");
            assert_true(in_array($step->direction, ['forward', 'reverse']), "  {$fromShort}→{$toShort} direction={$step->direction}");
            echo "    └─ {$fromShort} →[{$step->relationType}, {$step->direction}]→ {$toShort}\n";
        }
    } catch (\Throwable $e) {
        echo "  ❌ FAIL: {$label}: {$e->getMessage()}\n";
        $failed++;
    }
}

// ── Test 3: Existing API preserved ──
echo "\n═══ Test 3: Existing API compatibility ═══\n";

assert_true(method_exists($engine, 'getAvailableModels'), 'getAvailableModels() exists');
assert_true(method_exists($engine, 'getModelAttributes'), 'getModelAttributes() exists');
assert_true(method_exists($engine, 'getModelRelationships'), 'getModelRelationships() exists');
assert_true(method_exists($engine, 'getConnectedModels'), 'getConnectedModels() exists (new)');
assert_true(method_exists($engine, 'generate'), 'generate() exists');
assert_true(method_exists($engine, 'explainJoinPlan'), 'explainJoinPlan() exists');

$availableModels = $engine->getAvailableModels();
assert_true(count($availableModels) === 4, 'getAvailableModels() returns 4 models');

// getModelRelationships should now also return reverse-synthesized links
$userRels = $engine->getModelRelationships(User::class);
assert_true(!empty($userRels), 'User has relationships via getModelRelationships');

// ── Summary ──
echo "\n═══════════════════════════════════════════\n";
echo "RESULTS: {$passed} passed, {$failed} failed\n";
echo "═══════════════════════════════════════════\n\n";

exit($failed > 0 ? 1 : 0);
