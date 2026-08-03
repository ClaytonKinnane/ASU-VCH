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
    $triggerFiles = [
        'database/MilitaryRankDirectoryV2/triggers-a.sql',
        'database/MilitaryRankDirectoryV2/triggers-b.sql',
        'database/MilitaryRankDirectoryV2/triggers-c.sql',
        'database/MilitaryRankDirectoryV2/triggers-d.sql',
        'database/MilitaryRankDirectoryV2/triggers-e.sql',
        'database/MilitaryRankDirectoryV2/triggers-f.sql',
        'database/MilitaryRankDirectoryV2/triggers-g.sql',
    ];

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
        ...$triggerFiles,
    ];
    foreach ($required as $path) {
        source_check(is_file($root . '/' . $path), "Отсутствует файл {$path}.");
    }

    $compatibility = file_get_contents($root . '/database/OrganizationalStructureMigrationCompatibility.php');
    source_check(is_string($compatibility) && str_contains($compatibility, 'MilitaryRankDirectoryV2MigrationCompatibility.php'), 'Migration loader 012 не подключён.');
    source_check(str_contains($compatibility, 'MILITARY_RANK_V2_MIGRATION'), 'Migration 012 не маршрутизируется loader-ом.');

    $marker = file_get_contents($root . '/database/migrations/012_military_ranks_directory_v2.sql');
    source_check(is_string($marker) && str_contains($marker, 'COMPATIBILITY_LOADER_REQUIRED'), 'Migration marker 012 повреждён.');

    $triggerSql = '';
    foreach ($triggerFiles as $relative) {
        $content = file_get_contents($root . '/' . $relative);
        source_check(is_string($content), "Не удалось прочитать {$relative}.");
        source_check(preg_match('//u', $content) === 1, "SQL-шаблон {$relative} содержит некорректный UTF-8.");
        source_check(
            preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content) !== 1,
            "SQL-шаблон {$relative} содержит управляющие байты."
        );
        source_check(!str_contains($content, 'TRIGGGER'), "SQL-шаблон {$relative} содержит опечатку TRIGGGER.");
        source_check(!str_contains($content, 'WHEQH'), "SQL-шаблон {$relative} содержит повреждённый SQL token.");
        $triggerSql .= "\n" . $content;
    }
    source_check(substr_count($triggerSql, 'DROP TRIGGER IF EXISTS ') === 18, 'Ожидалось 18 DROP TRIGGER declarations.');
    source_check(substr_count($triggerSql, 'CREATE TRIGGER ') === 18, 'Ожидалось 18 CREATE TRIGGER declarations.');

    $publicationSql = file_get_contents($root . '/database/MilitaryRankDirectoryV2/publication.sql');
    source_check(is_string($publicationSql) && preg_match('//u', $publicationSql) === 1, 'Publication SQL содержит некорректный UTF-8.');
    source_check(
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $publicationSql) !== 1,
        'Publication SQL содержит управляющие байты.'
    );
    source_check(str_contains($publicationSql, 'START TRANSACTION;') && str_contains($publicationSql, 'COMMIT;'), 'Publication SQL не содержит транзакционные границы.');

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
