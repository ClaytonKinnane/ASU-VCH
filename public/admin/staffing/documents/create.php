<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Staffing/functions.php';

$user = staffing_require_action('staffing.registers.update');
staffing_handle_action(static function () use ($user): string {
    $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    $versionId = staffing_positive_int($_POST['version_id'] ?? null);
    staffing_service()->createDocument($registerId, $versionId, [
        'document_type' => staffing_post_string('document_type'),
        'document_date' => staffing_post_string('document_date'),
        'document_number' => staffing_post_string('document_number'),
        'title' => staffing_post_string('title'),
        'note' => staffing_post_nullable_string('note'),
        'document_role' => staffing_post_string('document_role'),
        'sort_order' => $_POST['sort_order'] ?? null,
        'expected_revision' => $_POST['expected_revision'] ?? null,
    ], (int) $user['id']);
    return staffing_safe_return_path($registerId, $versionId);
});
