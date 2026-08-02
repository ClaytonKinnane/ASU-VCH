<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

function source_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $required = [
        'app/Directory/MilitaryRankCompatibilityService.php',
        'app/Directory/MilitaryRankCatalogRepository.php',
        'database/MilitaryRankDirectoryV2MigrationCompatibility.php',
        'database/MilitaryRankDirectoryV2/Baseline.php',
        'database/MilitaryRankDirectoryV2/Definitions.php',
        'database/MilitaryRankDirectoryV2/Ddl.php',
        'database/MilitaryRankDirectoryV2/PublishedState.php',
        'database/MilitaryRankDirectoryV2/Recovery.php',
        'database/MilitaryRankDirectoryV2/SqlTemplates.php',
        'database/MilitaryRankDirectoryV2/publication.sql',
        'database/migrations/012_military_ranks_directory_v2.sql',
        'public/admin/directories/military-ranks.php',
        'themes/asu-blue/assets/css/military-ranks-v2.css',
        'themes/asu-light-blue/assets/css/military-ranks-v2.css',
        'themes/asu-evgeniya-rostova/assets/css/military-ranks-v2.css',
    ];
    foreach ($required as $path) {
        source_check(is_file($root . '/' . $path), "Отсутствует файл {$path}.");
    }

    $compatibility = file_get_contents($root . '/database/OrganizationalStructureMigrationCompatibility.php');
    source_check(is_string($compatibility) && str_contains($compatibility, 'MilitaryRankDirectoryV2MigrationCompatibility.php'), 'Migration loader 012 не подключён.');
    source_check(str_contains($compatibility, 'MILITARY_RANK_V2_MIGRATION'), 'Migration 012 не маршрутизируется loader-ом.');

    $marker = file_get_contents($root . '/database/migrations/012_military_ranks_directory_v2.sql');
    source_check(is_string($marker) && str_contains($marker, 'COMPATIBILITY_LOADER_REQUIRED'), 'Migration marker 012 повреждён.');

    $themes = require $root . '/config/themes.php';
    foreach (['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'] as $theme) {
        $assets = $themes['themes'][$theme]['required_assets'] ?? [];
        source_check(in_array('css/military-ranks-v2.css', $assets, true), "Тема {$theme} не регистрирует military-ranks-v2.css.");
    }

    $scanPaths = [
        'app/Directory/MilitaryRankCompatibilityService.php',
        'app/Directory/MilitaryRankCatalogRepository.php',
        'database/MilitaryRankDirectoryV2MigrationCompatibility.php',
        'database/MilitaryRankDirectoryV2',
        'database/migrations/012_military_ranks_directory_v2.sql',
        'public/admin/directories/military-ranks.php',
    ];
    $forbidden = ['staff_slot', 'military_position_definition', 'personnel_assignment', 'organizational_structure_version_id'];
    foreach ($scanPaths as $relative) {
        $path = $root . '/' . $relative;
        $files = is_dir($path) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) : [$path];
        foreach ($files as $file) {
            $filePath = $file instanceof SplFileInfo ? $file->getPathname() : $file;
            if (!is_file($filePath)) {
                continue;
            }
            $content = file_get_contents($filePath);
            source_check(is_string($content), "Не удалось прочитать {$filePath}.");
            foreach ($forbidden as $term) {
                source_check(!str_contains(strtolower($content), $term), "Запрещённый Staffing scope {$term} найден в {$filePath}.");
            }
        }
    }

    echo "MILITARY RANKS DIRECTORY V2 SOURCE CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANKS DIRECTORY V2 SOURCE CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
