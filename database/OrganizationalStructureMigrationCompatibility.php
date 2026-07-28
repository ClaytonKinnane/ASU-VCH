<?php

declare(strict_types=1);

function prepare_migration_sql_for_environment(
    PDO $pdo,
    string $schemaName,
    string $migrationName,
    string $sql
): string {
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

    $invalidConstraint = '/,\s*CONSTRAINT\s+chk_org_structure_nodes_self_parent\s+'
        . 'CHECK\s*\(\s*parent_node_id\s+IS\s+NULL\s+OR\s+parent_node_id\s*<>\s*id\s*\)/i';
    $sql = preg_replace($invalidConstraint, '', $sql, 1, $constraintCount);
    if (!is_string($sql) || $constraintCount !== 1) {
        throw new RuntimeException(
            'Не удалось применить MySQL 8.4 compatibility fix к chk_org_structure_nodes_self_parent.'
        );
    }

    $insertNeedle = <<<'SQL'
IF (SELECT status FROM organizational_structure_versions WHERE id = NEW.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_update
SQL;
    $insertReplacement = <<<'SQL'
IF (SELECT status FROM organizational_structure_versions WHERE id = NEW.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';
END IF;
IF NEW.parent_node_id IS NOT NULL AND NEW.id IS NOT NULL AND NEW.id <> 0 AND NEW.parent_node_id = NEW.id THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_update
SQL;
    $sql = str_replace($insertNeedle, $insertReplacement, $sql, $insertCount);
    if ($insertCount !== 1) {
        throw new RuntimeException('Не удалось усилить trigger вставки узла проверкой self-parent.');
    }

    $updateNeedle = <<<'SQL'
IF OLD.parent_node_id IS NULL AND NEW.parent_node_id IS NOT NULL THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ROOT_MOVE_FORBIDDEN';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_delete
SQL;
    $updateReplacement = <<<'SQL'
IF OLD.parent_node_id IS NULL AND NEW.parent_node_id IS NOT NULL THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ROOT_MOVE_FORBIDDEN';
END IF;
IF NEW.parent_node_id IS NOT NULL AND NEW.parent_node_id = OLD.id THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_delete
SQL;
    $sql = str_replace($updateNeedle, $updateReplacement, $sql, $updateCount);
    if ($updateCount !== 1) {
        throw new RuntimeException('Не удалось усилить trigger изменения узла проверкой self-parent.');
    }

    return $sql;
}
