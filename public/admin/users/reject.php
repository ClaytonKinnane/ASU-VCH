<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$actor = require_permission('security.users.reject');

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
    $result = user_rejection_service()->reject($userId, (int) $actor['id'], $reason);
} catch (Throwable $exception) {
    error_log('User rejection failed: ' . get_class($exception));
    flash('error', 'Учетная запись не отклонена из-за серверной ошибки.');
    redirect('/admin/users/view.php?id=' . $userId);
}

if ($result['ok']) {
    unset($_SESSION['_user_rejection'][$userId]);
    flash('success', 'Учетная запись «' . $result['username'] . '» отклонена.');
    redirect('/admin/users/view.php?id=' . $userId);
}

if (isset($result['errors']) && is_array($result['errors'])) {
    $preservedReason = mb_check_encoding($reason, 'UTF-8') && mb_strlen($reason, 'UTF-8') <= 500 ? $reason : '';
    $_SESSION['_user_rejection'][$userId] = [
        'errors' => $result['errors'],
        'values' => ['reason' => $preservedReason],
    ];
} else {
    flash('error', (string) ($result['error'] ?? 'Не удалось отклонить учетную запись.'));
}

redirect('/admin/users/view.php?id=' . $userId);
