<?php

declare(strict_types=1);
require dirname(__DIR__, 5) . '/app/bootstrap.php';
$user = military_positions_require_action('directories.military_positions.manage');
military_positions_handle_action(function () use ($user): string {
    $versionId = military_positions_positive_int($_POST['version_id'] ?? null);
    $revision = military_positions_positive_int($_POST['expected_catalog_revision'] ?? null, 'Некорректная редакция версии.');
    $input = [
        'name' => $_POST['name'] ?? null,
        'full_name' => $_POST['full_name'] ?? null,
        'short_name' => $_POST['short_name'] ?? null,
        'is_combined' => isset($_POST['is_combined']) ? '1' : '0',
        'source_type' => $_POST['source_type'] ?? null,
        'source_reference' => $_POST['source_reference'] ?? null,
        'note' => $_POST['note'] ?? null,
        'sort_order' => $_POST['sort_order'] ?? null,
        'change_reason' => $_POST['change_reason'] ?? null,
    ];
    military_position_catalog_service()->createEntry($versionId, $input, $revision, (int) $user['id']);
    return '/admin/directories/military-positions/version.php?id=' . $versionId;
});
