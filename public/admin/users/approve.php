<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('security.users.update');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}
require_csrf();

$userId = $_POST['user_id'] ?? null;
if (!is_scalar($userId) || !ctype_digit((string) $userId) || (int) $userId < 1) {
    flash('error', 'Некорректный идентификатор пользователя.');
    redirect('/admin/users.php');
}
$userId = (int) $userId;

$result = user_approval_service()->approve($userId, (int) $user['id']);
if ($result['ok']) {
    flash('success', 'Пользователь «' . $result['username'] . '» подтвержден и активирован.');
} else {
    flash('error', (string) ($result['error'] ?? 'Не удалось подтвердить учетную запись.'));
}
redirect('/admin/users/view.php?id=' . $userId);
