<?php

declare(strict_types=1);
require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Organization/functions.php';
$user = organization_require_action('organization.structures.update');
organization_handle_action(function () use ($user): string {
    $structureId = organization_positive_int($_POST['structure_id'] ?? null);
    $versionId = organization_positive_int($_POST['version_id'] ?? null);
    $revision = organization_positive_int($_POST['expected_revision'] ?? null, 'Некорректная редакция формы.');
    organizational_structure_service()->reorderNode(organization_positive_int($_POST['node_id'] ?? null), organization_post_string('direction'), $revision, (int) $user['id']);
    return organization_safe_return_path($structureId, $versionId);
});
