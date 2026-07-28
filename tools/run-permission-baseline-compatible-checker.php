<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$allowedCheckers = [
    'database/check-security-user-rejection.php',
    'database/check-security-user-archive-restore.php',
    'tools/check-military-ranks-directory-core.php',
    'tools/check-organizational-elements-directory-core.php',
];

$argument = $argv[1] ?? '';
$relativePath = str_replace('\\', '/', trim((string) $argument));
$relativePath = preg_replace('#^\./#', '', $relativePath) ?? '';

if (!in_array($relativePath, $allowedCheckers, true)) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: checker не разрешён: {$relativePath}\n");
    exit(2);
}

$sourcePath = $root . '/' . $relativePath;
$source = file_get_contents($sourcePath);
if ($source === false) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: checker не прочитан: {$sourcePath}\n");
    exit(3);
}

$requiredReplacements = [
    '$permissionCount === 19' => '$permissionCount >= 19',
    'Ожидалось 19 системных разрешений, найдено {$permissionCount}.'
        => 'Ожидалось не менее 19 системных разрешений, найдено {$permissionCount}.',
];

$prepared = $source;
foreach ($requiredReplacements as $search => $replace) {
    $prepared = str_replace($search, $replace, $prepared, $replacementCount);
    if ($replacementCount !== 1) {
        fwrite(
            STDERR,
            "REGRESSION ADAPTER FAILED: ожидалась одна обязательная замена в {$relativePath}, найдено {$replacementCount}.\n"
        );
        exit(4);
    }
}

$fixedOutput = 'echo "OK system permissions: 19\\n";';
$dynamicOutput = 'echo "OK system permissions: {$permissionCount}\\n";';
if (str_contains($prepared, $fixedOutput)) {
    $prepared = str_replace($fixedOutput, $dynamicOutput, $outputReplacementCount);
    if ($outputReplacementCount !== 1) {
        fwrite(
            STDERR,
            "REGRESSION ADAPTER FAILED: ожидалась одна замена вывода в {$relativePath}, найдено {$outputReplacementCount}.\n"
        );
        exit(5);
    }
} elseif (substr_count($prepared, $dynamicOutput) !== 1) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: вывод permission count не распознан в {$relativePath}.\n");
    exit(5);
}

if (str_contains($prepared, '$permissionCount === 19')) {
    fwrite(STDERR, "REGRESSION ADAPTER FAILED: точное ограничение 19 осталось в {$relativePath}.\n");
    exit(5);
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
