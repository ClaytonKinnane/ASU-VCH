<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.archive');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    staffing_service()->restoreRegister(
        $registerId,
        staffing_positive_int($_POST['expected_revision'] ?? null, 'Некорректная ревизия карточки.'),
        staffing_post_string('reason'),
        (int) $user['id']
    );
    return staffing_safe_return_path($registerId);
});
