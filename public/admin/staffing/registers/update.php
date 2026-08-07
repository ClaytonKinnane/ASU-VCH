<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.update');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    staffing_service()->updateRegister($registerId, [
        'name' => staffing_post_string('name'),
        'note' => staffing_post_nullable_string('note'),
        'expected_revision' => $_POST['expected_revision'] ?? null,
    ], (int) $user['id']);
    return staffing_safe_return_path($registerId);
});
