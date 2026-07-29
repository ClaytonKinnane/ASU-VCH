<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Organization/functions.php';
$user = organization_require_action('organization.structures.create');

organization_handle_action(function () use ($user): string {
    $structureId = organizational_structure_service()->createStructure(
        organization_post_string('code'),
        organization_post_string('display_name'),
        organization_post_nullable_string('short_name'),
        organization_positive_int($_POST['root_type_id'] ?? null),
        organization_post_string('root_name'),
        organization_post_nullable_string('root_short_name'),
        organization_post_string('change_reason'),
        (int) $user['id']
    );
    return organization_safe_return_path($structureId);
});
