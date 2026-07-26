<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$actor = require_permission('security.users.archive');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}
require_csrf();

$userIdValue = $_POST['user_id'] ?? null;
if (!is_scalar($userIdValue) || !ctype_digit((string) $userIdValue) || (int) $userIdValue < 1) {
    flash('error', 'Некорректный идентификатор пользователя.');
    redirect('/admin/users.php');
}
$userId = (int) $userIdValue;

$reasonValue = $_POST['reason'] ?? '';
$reason = is_string($reasonValue) ? $reasonValue : '';

try {
    $result = user_archive_restore_service()->archive($userId, (int) $actor['id'], $reason);
} catch (Throwable $exception) {
    error_log('User archive failed: ' . get_class($exception));
    $token = create_operation_result('error', 'Учетная запись не архивирована из-за серверной ошибки.');
    redirect('/admin/users/view.php?id=' . $userId . '&result=' . $token);
}

if ($result['ok']) {
    unset($_SESSION['_user_archive'][$userId]);
    $token = create_operation_result('success', 'Учетная запись «' . $result['username'] . '» архивирована.');
    redirect('/admin/users/view.php?id=' . $userId . '&result=' . $token);
}

if (isset($result['errors']) && is_array($result['errors'])) {
    $reasonCanBePreserved = mb_check_encoding($reason, 'UTF-8')
        && mb_strlen($reason, 'UTF-8') <= 500
        && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $reason) !== 1;
    $_SESSION['_user_archive'][$userId] = [
        'errors' => $result['errors'],
        'values' => ['reason' => $reasonCanBePreserved ? $reason : ''],
    ];
    redirect('/admin/users/view.php?id=' . $userId);
}

$token = create_operation_result(
    'error',
    (string) ($result['error'] ?? 'Не удалось архивировать учетную запись.')
);
redirect('/admin/users/view.php?id=' . $userId . '&result=' . $token);
