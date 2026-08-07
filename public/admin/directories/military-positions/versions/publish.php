<?php

declare(strict_types=1);
require dirname(__DIR__, 5) . '/app/bootstrap.php';
$user = military_positions_require_action('directories.military_positions.publish');
military_positions_handle_action(function () use ($user): string {
    $versionId = military_positions_positive_int($_POST['version_id'] ?? null);
    $revision = military_positions_positive_int($_POST['expected_catalog_revision'] ?? null, 'Некорректная редакция версии.');
    $reason = is_string($_POST['change_reason'] ?? null) ? $_POST['change_reason'] : '';
    military_position_catalog_service()->publish($versionId, $revision, $reason, (int) $user['id']);
    return '/admin/directories/military-positions/version.php?id=' . $versionId;
});
