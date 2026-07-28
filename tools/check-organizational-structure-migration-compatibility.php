<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/database/OrganizationalStructureMigrationCompatibility.php';
require_once $root . '/tools/PermissionBaselineRegressionAdapter.php';

$migrationPath = $root . '/database/migrations/009_organizational_structure_v1.sql';
$sql = file_get_contents($migrationPath);
if ($sql === false) {
    fwrite(STDERR, "FAIL: migration 009 не прочитана.\n");
    exit(1);
}

$organizationSchemaPath = $root . '/database/checks/organization/schema.php';
$organizationSchema = file_get_contents($organizationSchemaPath);
if ($organizationSchema === false) {
    fwrite(STDERR, "FAIL: organization schema checker не прочитан.\n");
    exit(1);
}

$regressionAdapterPath = $root . '/tools/run-permission-baseline-compatible-checker.php';
$regressionAdapter = file_get_contents($regressionAdapterPath);
if ($regressionAdapter === false) {
    fwrite(STDERR, "FAIL: permission regression adapter не прочитан.\n");
    exit(1);
}

$runnerPath = $root . '/tools/Test-OrganizationalStructureV1.ps1';
$runner = file_get_contents($runnerPath);
if ($runner === false) {
    fwrite(STDERR, "FAIL: Windows PowerShell runner не прочитан.\n");
    exit(1);
}

try {
    $prepared = transform_organizational_structure_migration_sql($sql);
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$hasUtf8Bom = static function (string $path): bool {
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return false;
    }

    try {
        return fread($handle, 3) === "\xEF\xBB\xBF";
    } finally {
        fclose($handle);
    }
};

$expectedAdaptedCheckers = [
    'database/check-security-user-rejection.php',
    'database/check-security-user-archive-restore.php',
    'tools/check-military-ranks-directory-core.php',
    'tools/check-organizational-elements-directory-core.php',
];
$adapterPreparationValid = permission_baseline_compatible_checker_paths() === $expectedAdaptedCheckers;
$dynamicOutput = 'echo "OK system permissions: {$permissionCount}\\n";';
foreach ($expectedAdaptedCheckers as $checker) {
    $checkerSource = file_get_contents($root . '/' . $checker);
    if (!is_string($checkerSource)) {
        $adapterPreparationValid = false;
        continue;
    }

    try {
        $preparedChecker = prepare_permission_baseline_compatible_checker($checkerSource, $checker);
        $adapterPreparationValid = $adapterPreparationValid
            && !str_contains($preparedChecker, '$permissionCount === 19')
            && substr_count($preparedChecker, '$permissionCount >= 19') === 1
            && substr_count($preparedChecker, $dynamicOutput) === 1;
    } catch (Throwable) {
        $adapterPreparationValid = false;
    }
}

$checks = [
    'таблиц после подготовки: 7' => preg_match_all('/^\s*CREATE\s+TABLE\b/im', $prepared) === 7,
    'triggers после подготовки: 16' => preg_match_all('/^\s*CREATE\s+TRIGGER\b/im', $prepared) === 16,
    'unsupported auto-increment CHECK удалён' => !str_contains($prepared, 'chk_org_structure_nodes_self_parent'),
    'self-parent защищён двумя trigger-проверками' => substr_count(
        $prepared,
        'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN'
    ) === 2,
    'DELIMITER отсутствует' => preg_match('/^\s*DELIMITER\b/im', $prepared) !== 1,
    'Windows PowerShell runner содержит UTF-8 BOM' => $hasUtf8Bom($runnerPath),
    'backup wrapper содержит UTF-8 BOM' => $hasUtf8Bom(
        $root . '/tools/Backup-Database.ps1'
    ),
    'organization checker использует ThemeRegistry' => str_contains(
        $organizationSchema,
        'theme_registry_service()'
    ),
    'organization checker не проверяет непубликуемый source theme path' => !str_contains(
        $organizationSchema,
        "\$root . '/themes/'"
    ),
    'permission regression adapter ограничен четырьмя checker-файлами' => permission_baseline_compatible_checker_paths()
        === $expectedAdaptedCheckers,
    'permission regression adapter реально готовит все четыре checker-файла' => $adapterPreparationValid,
    'CLI adapter использует протестированную функцию подготовки' => str_contains(
        $regressionAdapter,
        'prepare_permission_baseline_compatible_checker($source, $relativePath)'
    ),
    'runner использует permission regression adapter четыре раза' => substr_count(
        $runner,
        'run-permission-baseline-compatible-checker.php'
    ) === 4,
];

$failed = 0;
foreach ($checks as $label => $passed) {
    if ($passed) {
        echo "PASS: {$label}\n";
    } else {
        fwrite(STDERR, "FAIL: {$label}\n");
        $failed++;
    }
}

if ($failed !== 0) {
    exit(1);
}

echo "ORGANIZATIONAL STRUCTURE MIGRATION COMPATIBILITY CHECK PASSED\n";
exit(0);
