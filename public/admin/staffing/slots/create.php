<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.update');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    $versionId = staffing_positive_int($_POST['version_id'] ?? null);
    staffing_service()->createSlot($registerId, $versionId, [
        'organizational_structure_element_id' => $_POST['organizational_structure_element_id'] ?? null,
        'position_type_id' => $_POST['position_type_id'] ?? null,
        'position_variant_id' => $_POST['position_variant_id'] ?? null,
        'minimum_rank_id' => $_POST['minimum_rank_id'] ?? null,
        'maximum_rank_id' => $_POST['maximum_rank_id'] ?? null,
        'preferred_rank_id' => $_POST['preferred_rank_id'] ?? null,
        'internal_code' => staffing_post_nullable_string('internal_code'),
        'display_name' => staffing_post_string('display_name'),
        'normative_state' => staffing_post_string('normative_state'),
        'note' => staffing_post_nullable_string('note'),
        'sort_order' => $_POST['sort_order'] ?? null,
        'expected_revision' => $_POST['expected_revision'] ?? null,
        'vus_requirements' => staffing_parse_vus_requirements_from_post(),
    ], (int) $user['id']);
    return staffing_safe_return_path($registerId, $versionId);
});
