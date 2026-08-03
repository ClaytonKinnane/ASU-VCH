<?php

declare(strict_types=1);

const MILITARY_RANK_V2_MIGRATION = '012_military_ranks_directory_v2.sql';
const MILITARY_RANK_V1_CODE = 'rf-military-ranks-2026-07-27';
const MILITARY_RANK_V2_CODE = 'rf-military-ranks-staffing-scopes-v2';

/** @return list<array{code:string,name:string,parent_code:?string,sort_order:int}> */

require_once __DIR__ . '/MilitaryRankDirectoryV2/Definitions.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2/Baseline.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2/PublishedState.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2/Recovery.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2/Ddl.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2/SqlTemplates.php';

function prepare_military_rank_directory_v2_migration_sql(
    PDO $pdo,
    string $schemaName,
    string $migrationName,
    string $markerSql
): string {
    if ($migrationName !== MILITARY_RANK_V2_MIGRATION) {
        return $markerSql;
    }

    foreach ([
        'military_rank_catalog_versions',
        'military_rank_catalog_version_sources',
        'military_personnel_compositions',
        'military_rank_levels',
        'legal_sources',
    ] as $table) {
        if (!military_rank_v2_table_exists($pdo, $schemaName, $table)) {
            throw new RuntimeException("Migration 012 preflight: отсутствует таблица {$table}.");
        }
    }

    military_rank_v2_assert_v1_baseline($pdo);
    $hasLifecycle = military_rank_v2_column_exists(
        $pdo,
        $schemaName,
        'military_rank_catalog_versions',
        'lifecycle_status'
    );
    $v2 = military_rank_v2_version_by_code($pdo, MILITARY_RANK_V2_CODE, $hasLifecycle);

    $alreadyPublished = false;
    if (is_array($v2)) {
        if (!$hasLifecycle) {
            throw new RuntimeException('Migration 012 preflight: v2 существует без lifecycle schema.');
        }
        $status = (string) $v2['lifecycle_status'];
        if ($status === 'published') {
            $alreadyPublished = true;
        } elseif ($status === 'building') {
            military_rank_v2_assert_recoverable_building_state($pdo, (int) $v2['id']);
        } else {
            throw new RuntimeException('Migration 012 preflight: обнаружена противоречивая v2 не в building/published.');
        }
    }

    if (!$alreadyPublished) {
        $v1 = military_rank_v2_version_by_code($pdo, MILITARY_RANK_V1_CODE, $hasLifecycle);
        $currentCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM military_rank_catalog_versions WHERE is_current = 1'
        )->fetchColumn();
        if (!is_array($v1) || (int) $v1['is_current'] !== 1 || $currentCount !== 1) {
            throw new RuntimeException('Migration 012 preflight: до публикации v2 версия v1 должна быть единственной current.');
        }
        if ($hasLifecycle && (string) $v1['lifecycle_status'] !== 'published') {
            throw new RuntimeException('Migration 012 preflight: версия v1 должна иметь lifecycle published.');
        }
    }

    $ddl = military_rank_v2_ddl($pdo, $schemaName);
    $parts = [];
    if ($ddl !== []) {
        $parts[] = implode(";\n", $ddl) . ';';
    }
    $parts[] = military_rank_v2_trigger_sql();

    if ($alreadyPublished) {
        military_rank_v2_assert_published_state($pdo);
        $parts[] = 'SELECT 1 AS military_rank_v2_already_published;';
    } else {
        $parts[] = military_rank_v2_publication_sql();
    }

    return implode("\n\n", $parts);
}
