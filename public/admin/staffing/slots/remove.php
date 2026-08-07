<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.update');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    $versionId = staffing_positive_int($_POST['version_id'] ?? null);
    staffing_service()->removeSlot(
        $registerId,
        $versionId,
        staffing_positive_int($_POST['slot_id'] ?? null),
        staffing_positive_int($_POST['expected_revision'] ?? null, 'Некорректная ревизия версии.'),
        staffing_post_string('reason'),
        (int) $user['id']
    );
    return staffing_safe_return_path($registerId, $versionId);
});
