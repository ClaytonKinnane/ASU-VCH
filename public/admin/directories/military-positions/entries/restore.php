<?php

declare(strict_types=1);
require dirname(__DIR__, 5) . '/app/bootstrap.php';
$user = military_positions_require_action('directories.military_positions.manage');
military_positions_handle_action(function () use ($user): string {
    $versionId = military_positions_positive_int($_POST['version_id'] ?? null);
    $entryId = military_positions_positive_int($_POST['entry_id'] ?? null);
    $catalogRevision = military_positions_positive_int($_POST['expected_catalog_revision'] ?? null, 'Некорректная редакция версии.');
    $entryRevision = military_positions_positive_int($_POST['expected_entry_revision'] ?? null, 'Некорректная редакция должности.');
    $reason = is_string($_POST['change_reason'] ?? null) ? $_POST['change_reason'] : '';
    military_position_catalog_service()->restoreEntry($versionId, $entryId, $catalogRevision, $entryRevision, $reason, (int) $user['id']);
    return '/admin/directories/military-positions/version.php?id=' . $versionId;
});
