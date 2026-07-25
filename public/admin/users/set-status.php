<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_permission('security.users.block');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}
require_csrf();

$userIdValue = $_POST['user_id'] ?? null;
$activeValue = $_POST['is_active'] ?? null;
if (!is_scalar($userIdValue) || !ctype_digit((string) $userIdValue) || (int) $userIdValue < 1) {
    flash('error', 'Некорректный идентификатор пользователя.');
    redirect('/admin/users.php');
}
if (!is_scalar($activeValue) || !in_array((string) $activeValue, ['0', '1'], true)) {
    flash('error', 'Некорректное состояние учетной записи.');
    redirect('/admin/users/view.php?id=' . (int) $userIdValue);
}

$userId = (int) $userIdValue;
$result = user_status_service()->setActive($userId, (string) $activeValue === '1');
if ($result['ok']) {
    $action = $result['is_active'] ? 'разблокирован' : 'заблокирован';
    flash('success', 'Пользователь «' . $result['username'] . '» ' . $action . '.');
} else {
    flash('error', (string) ($result['error'] ?? 'Не удалось изменить состояние учетной записи.'));
}

redirect('/admin/users/view.php?id=' . $userId);
