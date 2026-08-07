<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.create');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    $versionId = staffing_service()->createVersion($registerId, [
        'organizational_structure_version_id' => $_POST['organizational_structure_version_id'] ?? null,
        'based_on_version_id' => $_POST['based_on_version_id'] ?? null,
        'version_label' => staffing_post_string('version_label'),
        'effective_from' => staffing_post_string('effective_from'),
        'change_reason' => staffing_post_string('change_reason'),
    ], (int) $user['id']);
    return staffing_safe_return_path($registerId, $versionId);
});
