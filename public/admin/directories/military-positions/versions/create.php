<?php

declare(strict_types=1);
require dirname(__DIR__, 5) . '/app/bootstrap.php';
$user = military_positions_require_action('directories.military_positions.manage');
military_positions_handle_action(function () use ($user): string {
    $versionId = military_position_catalog_service()->createDraft([
        'version_label' => $_POST['version_label'] ?? null,
        'effective_from' => $_POST['effective_from'] ?? null,
        'change_reason' => $_POST['change_reason'] ?? null,
        'expected_catalog_revision' => $_POST['expected_catalog_revision'] ?? null,
    ], (int) $user['id']);
    return '/admin/directories/military-positions/version.php?id=' . $versionId;
});
