<?php

declare(strict_types=1);
require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Organization/functions.php';
$user = organization_require_action('organization.structures.archive');
organization_handle_action(function () use ($user): string {
    $structureId = organization_positive_int($_POST['structure_id'] ?? null);
    organizational_structure_service()->archiveStructure($structureId, organization_post_string('reason'), (int) $user['id']);
    return organization_safe_return_path($structureId);
});
