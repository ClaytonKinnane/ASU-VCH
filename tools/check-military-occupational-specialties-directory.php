<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/database/MilitaryOccupationalSpecialtyMigrationCompatibility.php';

function mos_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "OK {$message}\n";
}

/** @param callable():void $operation */
function mos_expect_database_failure(callable $operation, string $message): void
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
    $migrationPath = $root . '/database/migrations/011_public_military_occupational_specialties_directory.sql';
    $marker = file_get_contents($migrationPath);
    mos_check(is_string($marker) && $marker !== '', 'migration 011 marker readable');
    $migration = load_military_occupational_specialty_migration_sql($root . '/database/migrations');
    mos_check($migration !== '', 'migration 011 archive and canonical SQL SHA-256 verified');
    mos_check(substr_count($migration, 'CREATE TABLE IF NOT EXISTS military_occupational_specialty_') === 9, '9 tables declared');
    mos_check(substr_count($migration, 'CREATE TRIGGER trg_mos_') === 26, '26 triggers declared');
    mos_check(!str_contains($migration, 'DELIMITER'), 'no client DELIMITER dependency');
    mos_check(str_contains($migration, 'rf-public-vus-2026-08-01'), 'catalog version seed declared');
    mos_check(!str_contains($migration, 'military_occupational_specialty_position_relations'), 'position relation table absent');
    mos_check(!str_contains($migration, 'military_occupational_specialty_rank_relations'), 'rank relation table absent');
    mos_check(!str_contains($migration, 'military_occupational_specialty_person_relations'), 'person relation table absent');
    mos_check(!str_contains($migration, 'military_occupational_specialty_equipment_relations'), 'equipment relation table absent');

    $bootstrap = file_get_contents($root . '/app/bootstrap.php');
    mos_check(
        is_string($bootstrap)
        && str_contains($bootstrap, "require_once __DIR__ . '/Directory/MilitaryOccupationalSpecialtyCatalogRepository.php';"),
        'bootstrap require'
    );
    mos_check(
        str_contains($bootstrap, 'function military_occupational_specialty_catalog_repository(): MilitaryOccupationalSpecialtyCatalogRepository'),
        'bootstrap factory'
    );

    $page = file_get_contents($root . '/public/admin/directories/military-occupational-specialties.php');
    mos_check(is_string($page) && str_contains($page, "require_permission('system.*.*')"), 'owner permission');
    mos_check(str_contains($page, 'military_occupational_specialty_catalog_repository()'), 'page repository factory');
    mos_check(!str_contains($page, '$_POST'), 'GET-only page');
    mos_check(str_contains($page, 'не является полным перечнем ВУС'), 'mandatory warning');
    mos_check(str_contains($page, 'rel="noopener noreferrer"'), 'safe external links');
    mos_check(str_contains($page, 'Связи с типами воинских должностей не публикуются'), 'no position relation statement');

    $directories = file_get_contents($root . '/public/admin/directories.php');
    mos_check(
        is_string($directories)
        && str_contains($directories, '/admin/directories/military-occupational-specialties.php'),
        'directory tile'
    );

    $runner = file_get_contents($root . '/tools/Test-MilitaryOccupationalSpecialtiesDirectory.ps1');
    mos_check(is_string($runner) && str_contains($runner, 'AUTOMATED_TESTING_STATUS=PASS'), 'PowerShell test runner');

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
    $migrationStmt->execute(['migration' => '011_public_military_occupational_specialties_directory.sql']);
    mos_check((int) $migrationStmt->fetchColumn() === 1, 'migration 011 registered');

    $versions = $pdo->query(
        "SELECT id, code, status, valid_to FROM military_occupational_specialty_catalog_versions "
        . "WHERE status = 'published' ORDER BY id"
    )->fetchAll();
    mos_check(count($versions) === 1, 'one published catalog version');
    $version = $versions[0];
    mos_check($version['code'] === 'rf-public-vus-2026-08-01', 'published version code');
    mos_check($version['valid_to'] === null, 'published version valid_to NULL');
    $versionId = (int) $version['id'];

    $counts = [
        'military_occupational_specialty_catalog_version_legal_sources' => 5,
        'military_occupational_specialty_official_source_snapshots' => 4,
        'military_occupational_specialty_code_segments' => 3,
        'military_occupational_specialty_public_context_domains' => 6,
        'military_occupational_specialty_personnel_scopes' => 3,
        'military_occupational_specialty_public_disclosures' => 2,
        'military_occupational_specialty_training_organizations' => 4,
        'military_occupational_specialty_training_programs' => 15,
    ];
    foreach ($counts as $table => $expected) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE catalog_version_id = :version_id");
        $stmt->execute(['version_id' => $versionId]);
        mos_check((int) $stmt->fetchColumn() === $expected, "{$table} count {$expected}");
    }

    $tableCount = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() "
        . "AND table_name LIKE 'military_occupational_specialty_%'"
    )->fetchColumn();
    mos_check((int) $tableCount === 9, 'database table count 9');

    $triggerCount = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema = DATABASE() "
        . "AND trigger_name LIKE 'trg_mos_%'"
    )->fetchColumn();
    mos_check((int) $triggerCount === 26, 'database trigger count 26');

    $permissionCount = $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
    mos_check((int) $permissionCount === 25, 'system permissions remain 25');

    $distributionSql =
        "SELECT identifier_kind, COUNT(*) AS n FROM ("
        . "SELECT identifier_kind FROM military_occupational_specialty_public_disclosures WHERE catalog_version_id = :v1 "
        . "UNION ALL SELECT identifier_kind FROM military_occupational_specialty_training_programs WHERE catalog_version_id = :v2"
        . ") q GROUP BY identifier_kind";
    $distributionStmt = $pdo->prepare($distributionSql);
    $distributionStmt->execute(['v1' => $versionId, 'v2' => $versionId]);
    $distribution = [];
    foreach ($distributionStmt->fetchAll() as $row) {
        $distribution[(string) $row['identifier_kind']] = (int) $row['n'];
    }
    mos_check(($distribution['full-code-complete'] ?? 0) === 2, 'full-code-complete count 2');
    mos_check(($distribution['official-program-identifier'] ?? 0) === 10, 'official-program-identifier count 10');
    mos_check(($distribution['base-specialty-number'] ?? 0) === 2, 'base-specialty-number count 2');
    mos_check(($distribution['none'] ?? 0) === 3, 'identifier-none count 3');

    $identifierStmt = $pdo->prepare(
        'SELECT '
        . '(SELECT COUNT(*) FROM military_occupational_specialty_public_disclosures '
        . ' WHERE catalog_version_id=:v1 AND raw_identifier IS NOT NULL) + '
        . '(SELECT COUNT(*) FROM military_occupational_specialty_training_programs '
        . ' WHERE catalog_version_id=:v2 AND raw_identifier IS NOT NULL)'
    );
    $identifierStmt->execute(['v1' => $versionId, 'v2' => $versionId]);
    mos_check((int) $identifierStmt->fetchColumn() === 14, 'records with identifiers 14');

    $qualificationStmt = $pdo->prepare(
        'SELECT '
        . '(SELECT COUNT(*) FROM military_occupational_specialty_public_disclosures '
        . ' WHERE catalog_version_id=:v1 AND qualification_name IS NOT NULL) + '
        . '(SELECT COUNT(*) FROM military_occupational_specialty_training_programs '
        . ' WHERE catalog_version_id=:v2 AND qualification_name IS NOT NULL)'
    );
    $qualificationStmt->execute(['v1' => $versionId, 'v2' => $versionId]);
    mos_check((int) $qualificationStmt->fetchColumn() === 9, 'records with qualifications 9');

    $expectedFingerprints = [
        'financial-university-vuc-current' => 'aef6efa0e0396c0f0b6d6e60f543ea65e3be1f0cdc7e8602e9950336f55f097b',
        'miigaik-vuc-applicant-current' => '84724d5f75d48028b1b5ca2b11cf734ab77b360708d24c17e57744c1580f6272',
        'chesu-vuc-current' => '082edf5027bae3547ffbf82e681f1a7f93bce35db8b07fe2b41d30cc90735010',
        'osu-vuc-career-officers-2026-04-01' => 'ec70ea5c554b4ad01d21b5cef76935bfbd2a0f734c413ea7b688701d2d678a74',
    ];
    $fingerprintStmt = $pdo->prepare(
        'SELECT evidence_fingerprint FROM military_occupational_specialty_official_source_snapshots '
        . 'WHERE catalog_version_id=:version_id AND code=:code'
    );
    foreach ($expectedFingerprints as $code => $hash) {
        $fingerprintStmt->execute(['version_id' => $versionId, 'code' => $code]);
        mos_check($fingerprintStmt->fetchColumn() === $hash, "evidence fingerprint {$code}");
    }

    $positionLike = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() "
        . "AND table_name LIKE 'military_occupational_specialty%position_relation%'"
    )->fetchColumn();
    mos_check((int) $positionLike === 0, 'position relation tables absent');

    $pdo->beginTransaction();
    try {
        mos_expect_database_failure(
            static function () use ($pdo, $versionId): void {
                $stmt = $pdo->prepare(
                    "INSERT INTO military_occupational_specialty_personnel_scopes "
                    . "(catalog_version_id, code, name, description, sort_order, created_at) "
                    . "VALUES (:v, 'forbidden', 'Запрещено', 'Проверка', 999, NOW())"
                );
                $stmt->execute(['v' => $versionId]);
            },
            'published child insert rejected'
        );

        mos_expect_database_failure(
            static function () use ($pdo, $versionId): void {
                $stmt = $pdo->prepare(
                    "UPDATE military_occupational_specialty_catalog_versions "
                    . "SET status='building' WHERE id=:id"
                );
                $stmt->execute(['id' => $versionId]);
            },
            'backward lifecycle transition rejected'
        );

        $code = 'mos-check-' . bin2hex(random_bytes(6));
        $insertVersion = $pdo->prepare(
            "INSERT INTO military_occupational_specialty_catalog_versions "
            . "(code,name,coverage_note,status,valid_from,valid_to,verified_at,created_by,created_at,published_at,superseded_at) "
            . "VALUES (:code,'Checker','Checker','building','2026-08-01',NULL,'2026-08-01',NULL,NOW(),NULL,NULL)"
        );
        $insertVersion->execute(['code' => $code]);
        $temporaryVersionId = (int) $pdo->lastInsertId();

        mos_expect_database_failure(
            static function () use ($pdo, $temporaryVersionId): void {
                $stmt = $pdo->prepare(
                    "INSERT INTO military_occupational_specialty_official_source_snapshots "
                    . "(catalog_version_id,code,publisher_name,publisher_type,title,source_url,published_on,verified_at,source_status,source_locator,evidence_summary,evidence_fingerprint,sort_order,created_at) "
                    . "VALUES (:v,'bad-url','Test','university-vuc','Test','http://example.test',NULL,'2026-08-01','current','test','test',REPEAT('a',64),1,NOW())"
                );
                $stmt->execute(['v' => $temporaryVersionId]);
            },
            'non-HTTPS official URL rejected'
        );

        $snapshot = $pdo->prepare(
            "INSERT INTO military_occupational_specialty_official_source_snapshots "
            . "(catalog_version_id,code,publisher_name,publisher_type,title,source_url,published_on,verified_at,source_status,source_locator,evidence_summary,evidence_fingerprint,sort_order,created_at) "
            . "VALUES (:v,'checker-source','Test','university-vuc','Test','https://example.test',NULL,'2026-08-01','current','test','test',REPEAT('a',64),1,NOW())"
        );
        $snapshot->execute(['v' => $temporaryVersionId]);
        $snapshotId = (int) $pdo->lastInsertId();

        $organization = $pdo->prepare(
            "INSERT INTO military_occupational_specialty_training_organizations "
            . "(catalog_version_id,code,name,organization_type,official_site,official_source_snapshot_id,sort_order,created_at) "
            . "VALUES (:v,'checker-org','Test','university-vuc','https://example.test',:s,1,NOW())"
        );
        $organization->execute(['v' => $temporaryVersionId, 's' => $snapshotId]);
        $organizationId = (int) $pdo->lastInsertId();

        $scope = $pdo->prepare(
            "INSERT INTO military_occupational_specialty_personnel_scopes "
            . "(catalog_version_id,code,name,description,sort_order,created_at) "
            . "VALUES (:v,'checker-scope','Test','Test',1,NOW())"
        );
        $scope->execute(['v' => $temporaryVersionId]);
        $scopeId = (int) $pdo->lastInsertId();

        mos_expect_database_failure(
            static function () use ($pdo, $temporaryVersionId, $organizationId, $snapshotId, $scopeId): void {
                $stmt = $pdo->prepare(
                    "INSERT INTO military_occupational_specialty_training_programs "
                    . "(catalog_version_id,code,organization_id,official_source_snapshot_id,personnel_scope_id,raw_identifier,identifier_kind,specialty_number,position_code,special_sign,qualification_name,program_name,program_kind,personnel_category_raw,service_context_raw,source_phrase,evidence_level,evidence_summary,program_status,published_on,valid_from,valid_to,verified_at,coverage_note,sort_order,created_at) "
                    . "VALUES (:v,'invalid-six',:o,:s,:p,'030400','official-program-identifier','030','400',NULL,NULL,'Test','reserve-officers','Test',NULL,'Test','official-program-code','Test','current',NULL,NULL,NULL,'2026-08-01','Test',1,NOW())"
                );
                $stmt->execute(['v' => $temporaryVersionId, 'o' => $organizationId, 's' => $snapshotId, 'p' => $scopeId]);
            },
            'six-digit program identifier with parsed components rejected'
        );
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    echo "MILITARY_OCCUPATIONAL_SPECIALTIES_DIRECTORY_CHECK=PASS\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
