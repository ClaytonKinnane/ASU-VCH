<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/database/MilitaryPositionMigrationCompatibility.php';

function mp_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "OK {$message}\n";
}

/** @param callable():void $operation */
function mp_expect_database_failure(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (PDOException) {
        echo "OK {$message}\n";
        return;
    }
    throw new RuntimeException($message . ' — операция неожиданно выполнена.');
}

try {
    $migrationFile = $root . '/database/migrations/010_military_positions_directory.sql';
    $migrationMarker = file_get_contents($migrationFile);
    mp_check(is_string($migrationMarker), 'migration marker readable');
    $migration = load_military_position_migration_sql($root . '/database/migrations');
    mp_check($migration !== '', 'migration parts assembled and SHA-256 verified');
    mp_check(substr_count($migration, 'CREATE TABLE IF NOT EXISTS military_position_') === 14, '14 tables declared');
    mp_check(substr_count($migration, 'CREATE TRIGGER trg_mp_') === 41, '41 triggers declared');
    mp_check(!str_contains($migration, 'DELIMITER'), 'no client DELIMITER dependency');
    mp_check(str_contains($migration, 'CALL validate_military_position_catalog_v1(1);'), 'postflight validation declared');

    $bootstrap = file_get_contents($root . '/app/bootstrap.php');
    mp_check(
        is_string($bootstrap)
        && str_contains($bootstrap, "require_once __DIR__ . '/Directory/MilitaryPositionCatalogRepository.php';"),
        'bootstrap require'
    );
    mp_check(
        str_contains($bootstrap, 'function military_position_catalog_repository(): MilitaryPositionCatalogRepository'),
        'bootstrap factory'
    );

    $page = file_get_contents($root . '/public/admin/directories/military-positions.php');
    mp_check(is_string($page) && str_contains($page, "require_permission('system.*.*')"), 'owner permission');
    mp_check(str_contains($page, 'military_position_catalog_repository()'), 'page repository factory');
    mp_check(!str_contains($page, '$_POST'), 'read-only page');
    mp_check(str_contains($page, 'не является полным перечнем штатных должностей'), 'mandatory warning');
    mp_check(str_contains($page, 'rel="noopener noreferrer"'), 'safe external links');

    $directories = file_get_contents($root . '/public/admin/directories.php');
    mp_check(
        is_string($directories) && str_contains($directories, '/admin/directories/military-positions.php'),
        'directory tile'
    );

    $runner = file_get_contents($root . '/tools/Test-MilitaryPositionsDirectory.ps1');
    mp_check(is_string($runner) && str_contains($runner, 'AUTOMATED_TESTING_STATUS=PASS'), 'PowerShell test runner');

    $localFile = $root . '/config/local.php';
    if (!is_file($localFile)) {
        echo "SKIP database checks: config/local.php отсутствует.\n";
        exit(0);
    }

    $app = require $root . '/config/app.php';
    $local = require $localFile;
    $config = array_replace_recursive($app, $local);
    $db = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migrationStmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migrationStmt->execute(['migration' => '010_military_positions_directory.sql']);
    mp_check((int) $migrationStmt->fetchColumn() === 1, 'migration registered');

    $versionRows = $pdo->query(
        "SELECT id, code, status, valid_to, rank_catalog_version_id, organizational_element_catalog_version_id "
        . "FROM military_position_catalog_versions WHERE status = 'published' ORDER BY id"
    )->fetchAll();
    mp_check(count($versionRows) === 1, 'one published catalog version');
    $version = $versionRows[0];
    mp_check($version['code'] === 'rf-military-positions-2026-07-31', 'published version code');
    mp_check($version['valid_to'] === null, 'published version valid_to is NULL');
    $versionId = (int) $version['id'];

    $catalogVersionCount = $pdo->prepare(
        'SELECT COUNT(*) FROM military_position_catalog_versions WHERE id = :version_id'
    );
    $catalogVersionCount->execute(['version_id' => $versionId]);
    mp_check((int) $catalogVersionCount->fetchColumn() === 1, 'military_position_catalog_versions count 1');

    $counts = [
        'military_position_catalog_version_sources' => 4,
        'military_position_source_entries' => 24,
        'military_position_source_entry_sources' => 28,
        'military_position_families' => 4,
        'military_position_types' => 34,
        'military_position_type_families' => 34,
        'military_position_variants' => 35,
        'military_position_composition_scopes' => 2,
        'military_position_composition_scope_members' => 3,
        'military_position_type_composition_scopes' => 34,
        'military_position_type_composition_scope_sources' => 35,
        'military_position_type_org_relations' => 29,
        'military_position_type_org_relation_sources' => 29,
    ];
    foreach ($counts as $table => $count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE catalog_version_id = :version_id");
        $stmt->execute(['version_id' => $versionId]);
        mp_check((int) $stmt->fetchColumn() === $count, "{$table} count {$count}");
    }

    $tableCount = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables "
        . "WHERE table_schema = DATABASE() AND table_name LIKE 'military_position_%'"
    )->fetchColumn();
    mp_check((int) $tableCount === 14, 'database table count');

    $triggerCount = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.triggers "
        . "WHERE trigger_schema = DATABASE() AND trigger_name LIKE 'trg_mp_%'"
    )->fetchColumn();
    mp_check((int) $triggerCount === 41, 'database trigger count');

    $requiredConstraints = [
        'uq_mp_catalog_versions_current_guard',
        'uq_mp_type_families_primary_guard',
        'uq_mp_type_composition_scopes_version_relation',
        'fk_mp_type_composition_sources_relation',
        'uq_mp_type_org_relations_full_version',
        'fk_mp_type_org_relation_sources_relation',
        'chk_mp_variants_normalization',
        'chk_mp_source_entries_grade',
    ];
    $constraintStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.table_constraints '
        . 'WHERE constraint_schema = DATABASE() AND constraint_name = :constraint_name'
    );
    foreach ($requiredConstraints as $constraintName) {
        $constraintStmt->execute(['constraint_name' => $constraintName]);
        mp_check((int) $constraintStmt->fetchColumn() === 1, "constraint {$constraintName}");
    }

    $rankRelationTables = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() "
        . "AND table_name LIKE 'military_position%rank_relation%'"
    )->fetchColumn();
    mp_check((int) $rankRelationTables === 0, 'rank relation tables absent');

    $permissionCount = $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
    mp_check((int) $permissionCount === 25, 'system permissions remain 25');

    $sourceRoleCounts = $pdo->prepare(
        'SELECT source_role, COUNT(*) AS row_count FROM military_position_source_entry_sources '
        . 'WHERE catalog_version_id = :version_id GROUP BY source_role ORDER BY source_role'
    );
    $sourceRoleCounts->execute(['version_id' => $versionId]);
    $roles = [];
    foreach ($sourceRoleCounts->fetchAll() as $row) {
        $roles[(string) $row['source_role']] = (int) $row['row_count'];
    }
    mp_check(($roles['base-provision'] ?? 0) === 24, 'base-provision evidence count 24');
    mp_check(($roles['amendment-operation'] ?? 0) === 4, 'amendment evidence count 4');

    $amendmentSet = $pdo->prepare(
        "SELECT GROUP_CONCAT(se.entry_code ORDER BY se.entry_code SEPARATOR ',') "
        . 'FROM military_position_source_entry_sources x '
        . 'JOIN military_position_source_entries se ON se.id = x.source_entry_id '
        . 'JOIN legal_sources s ON s.id = x.legal_source_id '
        . "WHERE x.catalog_version_id = :version_id AND s.code = 'defense-order-143-amendment'"
    );
    $amendmentSet->execute(['version_id' => $versionId]);
    mp_check(
        $amendmentSet->fetchColumn() === 'grade-20,grade-22,grade-29,grade-35',
        'amendment evidence exact set'
    );

    $departmentRelation = $pdo->prepare(
        'SELECT COUNT(*) FROM military_position_type_org_relations r '
        . 'JOIN military_position_types t ON t.id = r.position_type_id '
        . 'JOIN organizational_element_types o ON o.id = r.organizational_element_type_id '
        . "WHERE r.catalog_version_id = :version_id AND t.code = 'department-director' AND o.code = 'department'"
    );
    $departmentRelation->execute(['version_id' => $versionId]);
    mp_check((int) $departmentRelation->fetchColumn() === 0, 'department-director has no department relation');

    $variantGrades = $pdo->prepare(
        'SELECT COUNT(DISTINCT se.tariff_grade) FROM military_position_variants v '
        . 'JOIN military_position_source_entries se ON se.id = v.source_entry_id '
        . 'WHERE v.catalog_version_id = :version_id'
    );
    $variantGrades->execute(['version_id' => $versionId]);
    mp_check((int) $variantGrades->fetchColumn() === 24, 'distinct tariff grades 24');

    $primaryFamilyCoverage = $pdo->prepare(
        'SELECT COUNT(*) FROM ('
        . 'SELECT t.id, SUM(tf.is_primary = 1) AS primary_count '
        . 'FROM military_position_types t '
        . 'LEFT JOIN military_position_type_families tf ON tf.position_type_id = t.id '
        . 'WHERE t.catalog_version_id = :version_id GROUP BY t.id HAVING primary_count <> 1'
        . ') invalid_types'
    );
    $primaryFamilyCoverage->execute(['version_id' => $versionId]);
    mp_check((int) $primaryFamilyCoverage->fetchColumn() === 0, 'exactly one primary family per type');

    $pdo->beginTransaction();
    try {
        mp_expect_database_failure(
            static function () use ($pdo, $versionId): void {
                $stmt = $pdo->prepare(
                    "INSERT INTO military_position_families "
                    . "(catalog_version_id, code, name, description, sort_order, created_at) "
                    . "VALUES (:version_id, 'forbidden-after-publish', 'Запрещено', 'Проверка блокировки', 999, NOW(6))"
                );
                $stmt->execute(['version_id' => $versionId]);
            },
            'published child insert rejected'
        );

        mp_expect_database_failure(
            static function () use ($pdo, $versionId): void {
                $stmt = $pdo->prepare("UPDATE military_position_catalog_versions SET status = 'building' WHERE id = :id");
                $stmt->execute(['id' => $versionId]);
            },
            'backward lifecycle transition rejected'
        );

        $temporaryCode = 'mp-check-' . bin2hex(random_bytes(8));
        $insertVersion = $pdo->prepare(
            'INSERT INTO military_position_catalog_versions '
            . '(code, name, coverage_note, status, valid_from, valid_to, verified_at, '
            . 'rank_catalog_version_id, organizational_element_catalog_version_id, created_by, created_at) '
            . "VALUES (:code, 'Temporary checker version', 'Temporary checker version', 'building', "
            . "'2026-08-01', NULL, '2026-08-01', :rank_id, :org_id, NULL, NOW(6))"
        );
        $insertVersion->execute([
            'code' => $temporaryCode,
            'rank_id' => (int) $version['rank_catalog_version_id'],
            'org_id' => (int) $version['organizational_element_catalog_version_id'],
        ]);
        $temporaryVersionId = (int) $pdo->lastInsertId();

        $baseSourceId = (int) $pdo->query(
            "SELECT id FROM legal_sources WHERE code = 'defense-order-727-appendix-3' LIMIT 1"
        )->fetchColumn();
        $insertVersionSource = $pdo->prepare(
            'INSERT INTO military_position_catalog_version_sources '
            . '(catalog_version_id, legal_source_id, source_role, sort_order, created_at) '
            . "VALUES (:version_id, :source_id, 'base-act', 1, NOW(6))"
        );
        $insertVersionSource->execute(['version_id' => $temporaryVersionId, 'source_id' => $baseSourceId]);

        mp_expect_database_failure(
            static function () use ($pdo, $temporaryVersionId): void {
                $stmt = $pdo->prepare(
                    'INSERT INTO military_position_source_entries '
                    . '(catalog_version_id, entry_code, section_scope, tariff_grade, source_locator, evidence_summary, sort_order, created_at) '
                    . "VALUES (:version_id, 'invalid-grade', 'contract-officers', 51, 'check', 'check', 1, NOW(6))"
                );
                $stmt->execute(['version_id' => $temporaryVersionId]);
            },
            'tariff grade outside 1-50 rejected'
        );

        $insertEntry = $pdo->prepare(
            'INSERT INTO military_position_source_entries '
            . '(catalog_version_id, entry_code, section_scope, tariff_grade, source_locator, evidence_summary, sort_order, created_at) '
            . "VALUES (:version_id, 'checker-entry', 'contract-officers', 2, 'checker', 'checker', 1, NOW(6))"
        );
        $insertEntry->execute(['version_id' => $temporaryVersionId]);
        $temporaryEntryId = (int) $pdo->lastInsertId();

        $compositionRelation = $pdo->prepare(
            'SELECT position_type_id, composition_scope_id FROM military_position_type_composition_scopes '
            . 'WHERE catalog_version_id = :version_id ORDER BY position_type_id LIMIT 1'
        );
        $compositionRelation->execute(['version_id' => $versionId]);
        $compositionRow = $compositionRelation->fetch();
        mp_check(is_array($compositionRow), 'composition relation fixture found');

        mp_expect_database_failure(
            static function () use ($pdo, $temporaryVersionId, $temporaryEntryId, $baseSourceId, $compositionRow): void {
                $stmt = $pdo->prepare(
                    'INSERT INTO military_position_type_composition_scope_sources '
                    . '(catalog_version_id, position_type_id, composition_scope_id, source_entry_id, legal_source_id, '
                    . 'source_role, provision_detail, sort_order, created_at) '
                    . "VALUES (:version_id, :type_id, :scope_id, :entry_id, :source_id, "
                    . "'section-heading', 'cross-version check', 1, NOW(6))"
                );
                $stmt->execute([
                    'version_id' => $temporaryVersionId,
                    'type_id' => (int) $compositionRow['position_type_id'],
                    'scope_id' => (int) $compositionRow['composition_scope_id'],
                    'entry_id' => $temporaryEntryId,
                    'source_id' => $baseSourceId,
                ]);
            },
            'cross-version composition evidence rejected'
        );

        $orgRelation = $pdo->prepare(
            'SELECT position_type_id, organizational_element_catalog_version_id, '
            . 'organizational_element_type_id, relation_role '
            . 'FROM military_position_type_org_relations '
            . 'WHERE catalog_version_id = :version_id ORDER BY position_type_id LIMIT 1'
        );
        $orgRelation->execute(['version_id' => $versionId]);
        $orgRow = $orgRelation->fetch();
        mp_check(is_array($orgRow), 'organizational relation fixture found');

        mp_expect_database_failure(
            static function () use ($pdo, $temporaryVersionId, $temporaryEntryId, $baseSourceId, $orgRow): void {
                $stmt = $pdo->prepare(
                    'INSERT INTO military_position_type_org_relation_sources '
                    . '(catalog_version_id, position_type_id, organizational_element_catalog_version_id, '
                    . 'organizational_element_type_id, relation_role, source_entry_id, legal_source_id, '
                    . 'source_role, provision_detail, sort_order, created_at) '
                    . "VALUES (:version_id, :type_id, :org_version_id, :org_type_id, :relation_role, "
                    . ":entry_id, :source_id, 'designation-evidence', 'cross-version check', 1, NOW(6))"
                );
                $stmt->execute([
                    'version_id' => $temporaryVersionId,
                    'type_id' => (int) $orgRow['position_type_id'],
                    'org_version_id' => (int) $orgRow['organizational_element_catalog_version_id'],
                    'org_type_id' => (int) $orgRow['organizational_element_type_id'],
                    'relation_role' => (string) $orgRow['relation_role'],
                    'entry_id' => $temporaryEntryId,
                    'source_id' => $baseSourceId,
                ]);
            },
            'cross-version organizational evidence rejected'
        );
    } finally {
        $pdo->rollBack();
    }

    echo "MILITARY_POSITIONS_DIRECTORY_CHECK=PASS\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
