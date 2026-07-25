<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_permission('security.users.update');

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
$result = user_update_service()->update($userId, $_POST);

if ($result['ok']) {
    flash('success', 'Основные данные пользователя «' . $result['username'] . '» сохранены.');
} else {
    $_SESSION['_user_edit'][$userId] = [
        'errors' => $result['errors'],
        'values' => [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'display_name' => trim((string) ($_POST['display_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'is_temporary' => isset($_POST['is_temporary']),
            'must_change_password' => isset($_POST['must_change_password']),
        ],
    ];
    flash('error', 'Основные данные не сохранены. Исправьте ошибки формы.');
}

redirect('/admin/users/view.php?id=' . $userId);
