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

$themeManagementCheckerPath = $root . '/database/check-theme-management.php';
$themeManagementChecker = file_get_contents($themeManagementCheckerPath);
if ($themeManagementChecker === false) {
    fwrite(STDERR, "FAIL: theme management checker не прочитан.\n");
    exit(1);
}

$uiPolishCheckerPath = $root . '/tools/check-organizational-structure-ui-polish.php';
$uiPolishChecker = file_get_contents($uiPolishCheckerPath);
if ($uiPolishChecker === false) {
    fwrite(STDERR, "FAIL: UI polish checker не прочитан.\n");
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
$expectedDeployThemePathCheckers = [
    'tools/check-military-ranks-directory-core.php',
    'tools/check-organizational-elements-directory-core.php',
];
$adapterPreparationValid = permission_baseline_compatible_checker_paths() === $expectedAdaptedCheckers;
$deployThemePathPreparationValid = deploy_theme_path_compatible_checker_paths() === $expectedDeployThemePathCheckers;
$dynamicOutput = 'echo "OK system permissions: {$permissionCount}\\n";';
$legacyThemePath = '$root . \'/themes/\' . $themeSlug . \'/assets/css/directories.css\'';
foreach ($expectedAdaptedCheckers as $checker) {
    $checkerSource = file_get_contents($root . '/' . $checker);
    if (!is_string($checkerSource)) {
        $adapterPreparationValid = false;
        $deployThemePathPreparationValid = false;
        continue;
    }

    try {
        $preparedChecker = prepare_permission_baseline_compatible_checker($checkerSource, $checker);
        $adapterPreparationValid = $adapterPreparationValid
            && !str_contains($preparedChecker, '$permissionCount === 19')
            && substr_count($preparedChecker, '$permissionCount >= 19') === 1
            && substr_count($preparedChecker, $dynamicOutput) === 1;
        if (in_array($checker, $expectedDeployThemePathCheckers, true)) {
            $deployThemePathPreparationValid = $deployThemePathPreparationValid
                && !str_contains($preparedChecker, $legacyThemePath)
                && str_contains($preparedChecker, "is_dir(\$root . '/public/themes')");
        }
    } catch (Throwable) {
        $adapterPreparationValid = false;
        if (in_array($checker, $expectedDeployThemePathCheckers, true)) {
            $deployThemePathPreparationValid = false;
        }
    }
}

$themeExpectedAssetNeedle = <<<'PHP'
'css/theme-management.css', 'css/directories.css', 'css/organization.css',
PHP;
$uiPolishThemeRootNeedle = <<<'PHP'
$themeRoot = is_dir($root . '/public/themes')
PHP;
$uiPolishThemeReadNeedle = <<<'PHP'
$themeCss[$slug] = ui_polish_read($themeRoot . "/{$slug}/assets/css/organization.css");
PHP;

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
    'theme management checker ожидает organization.css' => str_contains(
        $themeManagementChecker,
        $themeExpectedAssetNeedle
    ),
    'UI polish checker использует опубликованный theme path' => str_contains(
        $uiPolishChecker,
        $uiPolishThemeRootNeedle
    ) && str_contains(
        $uiPolishChecker,
        $uiPolishThemeReadNeedle
    ),
    'permission regression adapter ограничен четырьмя checker-файлами' => permission_baseline_compatible_checker_paths()
        === $expectedAdaptedCheckers,
    'permission regression adapter реально готовит все четыре checker-файла' => $adapterPreparationValid,
    'directory regression adapter использует опубликованные темы' => $deployThemePathPreparationValid,
    'CLI adapter использует протестированную функцию подготовки' => str_contains(
        $regressionAdapter,
        'prepare_permission_baseline_compatible_checker($source, $relativePath)'
    ),
    'runner использует permission regression adapter четыре раза' => substr_count(
        $runner,
        'run-permission-baseline-compatible-checker.php'
    ) === 4,
    'runner повторяет GitHub fetch при TLS-сбое' => str_contains(
        $runner,
        "Invoke-ExternalWithRetry 'git' @('fetch', 'origin')"
    ),
    'runner не выполняет повторный сетевой git pull' => str_contains(
        $runner,
        "Invoke-External 'git' @('merge', '--ff-only', \"origin/\$ExpectedBranch\")"
    ) && !str_contains($runner, "@('pull', '--ff-only')"),
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
