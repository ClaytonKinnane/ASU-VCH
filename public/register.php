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

if ($password !== $confirmation) {
    flash('error', 'Пароли не совпадают.');
    redirect('/');
}

try {
    $result = bootstrap_owner_service()->createOwner(
        username: $username,
        displayName: $displayName,
        password: $password,
        email: $email !== '' ? $email : null
    );

    session_regenerate_id(true);
    $_SESSION['user_id'] = $result['user_id'];
    redirect('/admin/');
} catch (InvalidArgumentException | RuntimeException $exception) {
    flash('error', $exception->getMessage());
    redirect('/');
} catch (Throwable) {
    flash('error', 'Не удалось создать владельца системы.');
    redirect('/');
}
