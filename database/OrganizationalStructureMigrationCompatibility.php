<?php

declare(strict_types=1);

require_once __DIR__ . '/MilitaryPositionMigrationCompatibility.php';
require_once __DIR__ . '/MilitaryOccupationalSpecialtyMigrationCompatibility.php';
require_once __DIR__ . '/MilitaryRankDirectoryV2MigrationCompatibility.php';

function transform_organizational_structure_migration_sql(string $sql): string
{
    $invalidConstraint = '/,\s*CONSTRAINT\s+chk_org_structure_nodes_self_parent\s+'
        . 'CHECK\s*\(\s*parent_node_id\s+IS\s+NULL\s+OR\s*parent_node_id\s*<>\s*id\s*\)/i';
    $sql = preg_replace($invalidConstraint, '', $sql, 1, $constraintCount);
    if (!is_string($sql) || $constraintCount !== 1) {
        throw new RuntimeException(
            'Не удалось применить MySQL 8.4 compatibility fix к chk_org_structure_nodes_self_parent.'
        );
    }

    $insertPattern = '/('
        . 'CREATE\s+TRIGGER\s+trg_org_structure_nodes_before_insert.*?'
        . "SIGNAL\s+SQLSTATE\s+'45000'\s+SET\s+MESSAGE_TEXT\s*=\s*'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';\s*"
        . 'END\s+IF;'
        . ')(\s*END;\s*CREATE\s+TRIGGER\s+trg_org_structure_nodes_before_update)/is';
    $insertReplacement = <<<'SQL'
$1
IF NEW.parent_node_id IS NOT NULL AND NEW.id IS NOT NULL AND NEW.id <> 0 AND NEW.parent_node_id = NEW.id THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN';
END IF;$2
SQL;
    $sql = preg_replace($insertPattern, $insertReplacement, $sql, 1, $insertCount);
    if (!is_string($sql) || $insertCount !== 1) {
        throw new RuntimeException('Не удалось усилить trigger вставки узла проверкой self-parent.');
    }

    $updatePattern = '/('
        . 'CREATE\s+TRIGGER\s+trg_org_structure_nodes_before_update.*?'
        . "SIGNAL\s+SQLSTATE\s+'45000'\s+SET\s+MESSAGE_TEXT\s*=\s*'ORG_STRUCTURE_ROOT_MOVE_FORBIDDEN';\s*"
        . 'END\s+IF;'
        . ')(\s*END;\s*CREATE\s+TRIGGER\s+trg_org_structure_nodes_before_delete)/is';
    $updateReplacement = <<<'SQL'
$1
IF NEW.parent_node_id IS NOT NULL AND NEW.parent_node_id = OLD.id THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN';
END IF;$2
SQL;
    $sql = preg_replace($updatePattern, $updateReplacement, $sql, 1, $updateCount);
    if (!is_string($sql) || $updateCount !== 1) {
        throw new RuntimeException('Не удалось усилить trigger изменения узла проверкой self-parent.');
    }

    return $sql;
}

function prepare_migration_sql_for_environment(
    PDO $pdo,
    string $schemaName,
    string $migrationName,
    string $sql
): string {
    if ($migrationName === '010_military_positions_directory.sql') {
        return load_military_position_migration_sql(__DIR__ . '/migrations');
    }
    if ($migrationName === '011_public_military_occupational_specialties_directory.sql') {
        return load_military_occupational_specialty_migration_sql(__DIR__ . '/migrations');
    }
    if ($migrationName === MILITARY_RANK_V2_MIGRATION) {
        return prepare_military_rank_directory_v2_migration_sql(
            $pdo,
            $schemaName,
            $migrationName,
            $sql
        );
    }

    if ($migrationName !== '009_organizational_structure_v1.sql') {
        return $sql;
    }

    $tables = [
        'organizational_structures',
        'organizational_structure_elements',
        'organizational_structure_versions',
        'organizational_structure_documents',
        'organizational_structure_version_documents',
        'organizational_structure_nodes',
        'organizational_structure_change_events',
    ];
    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name'
    );
    foreach ($tables as $table) {
        $tableCheck->execute(['schema_name' => $schemaName, 'table_name' => $table]);
        if ((int) $tableCheck->fetchColumn() !== 1) {
            continue;
        }

        $rowCount = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        if ($rowCount !== 0) {
            throw new RuntimeException(
                "Migration 009 не зарегистрирована, но таблица {$table} содержит {$rowCount} строк. "
                . 'Автоматическое продолжение частичной DDL запрещено.'
            );
        }
    }

    return transform_organizational_structure_migration_sql($sql);
}
