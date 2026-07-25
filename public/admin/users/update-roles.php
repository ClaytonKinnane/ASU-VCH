<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('security.users.assign_roles');

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
$roleIds = is_array($_POST['role_ids'] ?? null) ? $_POST['role_ids'] : [];
$actorIsOwner = in_array('system_owner', current_user_role_codes(), true);
$result = user_role_update_service()->update($userId, $roleIds, (int) $user['id'], $actorIsOwner);

if ($result['ok']) {
    flash('success', 'Роли пользователя «' . $result['username'] . '» сохранены.');
} else {
    flash('error', (string) ($result['error'] ?? 'Не удалось сохранить роли пользователя.'));
}

redirect('/admin/users/view.php?id=' . $userId);
