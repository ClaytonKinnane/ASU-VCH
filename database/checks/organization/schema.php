<?php

declare(strict_types=1);

return static function (PDO $pdo, string $root, callable $assert): array {
    $expectedTables = [
        'organizational_structures',
        'organizational_structure_elements',
        'organizational_structure_versions',
        'organizational_structure_documents',
        'organizational_structure_version_documents',
        'organizational_structure_nodes',
        'organizational_structure_change_events',
    ];
    $tableStmt = $pdo->prepare(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    foreach ($expectedTables as $table) {
        $tableStmt->execute(['table_name' => $table]);
        $assert($tableStmt->fetchColumn() === $table, "таблица {$table} существует");
    }

    $expectedPermissions = [
        'organization.structures.view',
        'organization.structures.create',
        'organization.structures.update',
        'organization.structures.publish',
        'organization.structures.archive',
        'organization.structures.history',
    ];
    $placeholders = implode(',', array_fill(0, count($expectedPermissions), '?'));
    $permissionStmt = $pdo->prepare('SELECT code FROM permissions WHERE code IN (' . $placeholders . ') ORDER BY code');
    $permissionStmt->execute($expectedPermissions);
    $actualPermissions = array_map('strval', $permissionStmt->fetchAll(PDO::FETCH_COLUMN));
    $expectedSorted = $expectedPermissions;
    sort($expectedSorted);
    $assert($actualPermissions === $expectedSorted, 'созданы все шесть permissions');

    $roleCount = (int) $pdo->query('SELECT COUNT(*) FROM roles WHERE is_system = 1')->fetchColumn();
    $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM permissions WHERE is_system = 1')->fetchColumn();
    $assert($roleCount === 4, 'системных ролей осталось 4');
    $assert($permissionCount === 25, 'системных permissions стало 25');

    $assignmentStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM role_permissions rp '
        . 'JOIN roles r ON r.id = rp.role_id '
        . 'JOIN permissions p ON p.id = rp.permission_id '
        . "WHERE r.code IN ('administrator', 'operator', 'viewer') AND p.code IN (" . $placeholders . ')'
    );
    $assignmentStmt->execute($expectedPermissions);
    $assert((int) $assignmentStmt->fetchColumn() === 0, 'обычным системным ролям новые permissions автоматически не назначены');

    $expectedTriggers = [
        'trg_org_structures_before_update',
        'trg_org_structures_before_delete',
        'trg_org_structure_elements_before_update',
        'trg_org_structure_elements_before_delete',
        'trg_org_structure_versions_before_update',
        'trg_org_structure_versions_before_delete',
        'trg_org_structure_nodes_before_insert',
        'trg_org_structure_nodes_before_update',
        'trg_org_structure_nodes_before_delete',
        'trg_org_structure_version_documents_before_insert',
        'trg_org_structure_version_documents_before_update',
        'trg_org_structure_version_documents_before_delete',
        'trg_org_structure_documents_before_update',
        'trg_org_structure_documents_before_delete',
        'trg_org_structure_change_events_before_update',
        'trg_org_structure_change_events_before_delete',
    ];
    $triggerStmt = $pdo->prepare(
        'SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = :trigger_name'
    );
    foreach ($expectedTriggers as $trigger) {
        $triggerStmt->execute(['trigger_name' => $trigger]);
        $assert($triggerStmt->fetchColumn() === $trigger, "trigger {$trigger} существует");
    }

    foreach (['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'] as $theme) {
        $assert(is_file($root . '/themes/' . $theme . '/assets/css/organization.css'), "тема {$theme} содержит organization.css");
    }
    $assert(is_file($root . '/public/assets/js/organization-tree.js'), 'общий JavaScript дерева существует');

    $ownerStmt = $pdo->query(
        "SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' AND u.is_active = 1 AND u.approval_status = 'approved' AND u.deleted_at IS NULL LIMIT 1"
    );
    $actorId = (int) $ownerStmt->fetchColumn();
    if ($actorId < 1) {
        throw new RuntimeException('Активный владелец системы не найден.');
    }

    $catalog = organizational_element_catalog_repository()->currentVersion();
    $rootTypes = organizational_structure_repository()->rootTypesForCatalog((int) $catalog['id']);
    $allTypes = organizational_structure_repository()->typesForCatalog((int) $catalog['id']);
    if ($rootTypes === [] || $allTypes === []) {
        throw new RuntimeException('Справочник типов не содержит необходимых записей.');
    }

    return [
        'actor_id' => $actorId,
        'catalog' => $catalog,
        'root_types' => $rootTypes,
        'all_types' => $allTypes,
    ];
};
