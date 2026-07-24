<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
require_csrf();

$identifier = mb_strtolower(trim((string) ($_POST['identifier'] ?? '')), 'UTF-8');
$password = (string) ($_POST['password'] ?? '');

$stmt = db()->prepare('SELECT id, password_hash FROM users WHERE (username_canonical = :identifier OR email_canonical = :identifier) AND is_active = 1 AND deleted_at IS NULL LIMIT 1');
$stmt->execute(['identifier' => $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    flash('error', 'Неверное имя пользователя или пароль.');
    redirect('/');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
$update = db()->prepare('UPDATE users SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id');
$update->execute(['last_login_at' => $now, 'updated_at' => $now, 'id' => (int) $user['id']]);
redirect('/admin/');
