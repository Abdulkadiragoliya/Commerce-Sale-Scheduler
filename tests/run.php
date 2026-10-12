<?php
declare(strict_types=1);

/**
 * Automated Test Runner for Commerce Sale Scheduler
 */

$app = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

echo "\n========================================================\n";
echo " Running Commerce Sale Scheduler Test Suite\n";
echo " Craft CMS: " . Craft::$app->getVersion() . " | Craft Commerce: " . \craft\commerce\Plugin::getInstance()->getVersion() . "\n";
echo "========================================================\n\n";

$testFiles = glob(__DIR__ . '/unit/*Test.php');
$totalTests = 0;
$passed = 0;
$failed = 0;

foreach ($testFiles as $file) {
    require_once $file;
    $className = 'abdulkadiragoliya\\commercesaleschedulertests\\unit\\' . basename($file, '.php');

    if (!class_exists($className)) {
        continue;
    }

    $ref = new ReflectionClass($className);
    $instance = new $className();
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

    echo "Running " . basename($file) . ":\n";

    foreach ($methods as $method) {
        if (!str_starts_with($method->getName(), 'test')) {
            continue;
        }

        $testName = $method->getName();
        $totalTests++;

        try {
            $instance->$testName();
            echo "  [PASS] {$testName}\n";
            $passed++;
        } catch (\Throwable $e) {
            echo "  [FAIL] {$testName}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
            $failed++;
        }
    }
    echo "\n";
}

echo "--------------------------------------------------------\n";
echo "Test Results: {$passed} passed, {$failed} failed (Total: {$totalTests})\n";
echo "--------------------------------------------------------\n\n";

exit($failed === 0 ? 0 : 1);
