<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
require_csrf();

$username = trim((string) ($_POST['username'] ?? ''));
$displayName = trim((string) ($_POST['display_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmation = (string) ($_POST['password_confirmation'] ?? '');

if ($username === '' || $displayName === '' || strlen($username) < 3 || strlen($password) < 5 || $password !== $confirmation) {
    flash('error', 'Проверьте заполнение формы и совпадение паролей.');
    redirect('/');
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $pdo->query("SELECT GET_LOCK('asu_vch_first_owner', 10)")->fetchColumn();

    $completed = installation_completed();
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($completed || $userCount > 0) {
        throw new RuntimeException('Первичная регистрация уже отключена.');
    }

    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'system_owner' LIMIT 1")->fetchColumn();
    if ($roleId < 1) {
        throw new RuntimeException('Системная роль не подготовлена. Запустите установку БД.');
    }

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO users (username, username_canonical, email, email_canonical, password_hash, display_name, is_active, is_temporary, must_change_password, created_at, updated_at) VALUES (:username, :username_canonical, :email, :email_canonical, :password_hash, :display_name, 1, 0, 0, :created_at, :updated_at)');
    $stmt->execute([
        'username' => $username,
        'username_canonical' => mb_strtolower($username, 'UTF-8'),
        'email' => $email !== '' ? $email : null,
        'email_canonical' => $email !== '' ? mb_strtolower($email, 'UTF-8') : null,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'display_name' => $displayName,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (:user_id, :role_id, :assigned_at)');
    $stmt->execute(['user_id' => $userId, 'role_id' => $roleId, 'assigned_at' => $now]);

    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = '1', updated_at = :updated_at WHERE setting_key = 'installation_completed'");
    $stmt->execute(['updated_at' => $now]);

    $pdo->commit();
    $pdo->query("SELECT RELEASE_LOCK('asu_vch_first_owner')");
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    redirect('/admin/');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    try { $pdo->query("SELECT RELEASE_LOCK('asu_vch_first_owner')"); } catch (Throwable) {}
    flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Не удалось создать владельца системы.');
    redirect('/');
}
