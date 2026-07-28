<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';
require_once $root . '/app/Organization/functions.php';

$pdo = db();
$failures = [];
$passes = [];

$assert = static function (bool $condition, string $message) use (&$failures, &$passes): void {
    if ($condition) {
        $passes[] = $message;
        echo "PASS: {$message}\n";
    } else {
        $failures[] = $message;
        echo "FAIL: {$message}\n";
    }
};

try {
    $checkSchema = require __DIR__ . '/checks/organization/schema.php';
    $context = $checkSchema($pdo, $root, $assert);
    $checkScenario = require __DIR__ . '/checks/organization/scenario.php';
    $checkScenario(
        $pdo,
        $assert,
        (int) $context['actor_id'],
        $context['catalog'],
        $context['root_types'],
        $context['all_types']
    );
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo PHP_EOL . 'PASS: ' . count($passes) . PHP_EOL;
echo 'FAIL: ' . count($failures) . PHP_EOL;
exit($failures === [] ? 0 : 1);
