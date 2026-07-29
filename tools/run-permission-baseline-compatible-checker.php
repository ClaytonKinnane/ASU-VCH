<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once __DIR__ . '/PermissionBaselineRegressionAdapter.php';

$argument = $argv[1] ?? '';
$relativePath = str_replace('\\', '/', trim((string) $argument));
$relativePath = preg_replace('#^\./#', '', $relativePath) ?? '';

if (!in_array($relativePath, permission_baseline_compatible_checker_paths(), true)) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: checker не разрешён: {$relativePath}\n");
    exit(2);
}

$sourcePath = $root . '/' . $relativePath;
$source = file_get_contents($sourcePath);
if ($source === false) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: checker не прочитан: {$sourcePath}\n");
    exit(3);
}

try {
    $prepared = prepare_permission_baseline_compatible_checker($source, $relativePath);
} catch (Throwable $exception) {
    fwrite(STDERR, 'REGRESSION ADAPTER FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(4);
}

$temporaryPath = dirname($sourcePath)
    . '/.asu-vch-regression-'
    . pathinfo($sourcePath, PATHINFO_FILENAME)
    . '-'
    . bin2hex(random_bytes(8))
    . '.php';
$adapterExitCode = 6;

try {
    if (file_put_contents($temporaryPath, $prepared, LOCK_EX) === false) {
        throw new RuntimeException("Не удалось создать временный checker: {$temporaryPath}");
    }

    echo "REGRESSION_ADAPTER_CHECKER={$relativePath}\n";
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($temporaryPath), $adapterExitCode);
} catch (Throwable $exception) {
    fwrite(STDERR, 'REGRESSION ADAPTER FAILED: ' . $exception->getMessage() . PHP_EOL);
    $adapterExitCode = 6;
} finally {
    if (is_file($temporaryPath)) {
        @unlink($temporaryPath);
    }
}

exit($adapterExitCode);
