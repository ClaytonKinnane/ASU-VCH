<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.create');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_service()->createRegister([
        'code' => staffing_post_string('code'),
        'name' => staffing_post_string('name'),
        'organizational_structure_id' => staffing_post_string('organizational_structure_id'),
        'note' => staffing_post_nullable_string('note'),
    ], (int) $user['id']);
    return staffing_safe_return_path($registerId);
}, '/admin/staffing/registers.php');
