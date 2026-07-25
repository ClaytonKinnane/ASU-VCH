<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$app = require $root . '/config/app.php';
$localFile = $root . '/config/local.php';
if (!is_file($localFile)) {
    fwrite(STDERR, "Не найден config/local.php.\n");
    exit(1);
}

require_once $root . '/app/Security/RequiredPasswordChangeService.php';

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];
$userId = null;
$usernameCanonical = 'password_check_' . bin2hex(random_bytes(6));
$oldPassword = 'TempPass1234';
$newPassword = 'ChangedPass5678';
$exitCode = 0;

function check_condition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $insert = $pdo->prepare(
        'INSERT INTO users (username, username_canonical, email, email_canonical, password_hash, display_name, is_active, is_temporary, must_change_password, last_login_at, created_at, updated_at, deleted_at, created_by, creation_reason, approval_status, approved_by, approved_at) '
        . "VALUES (:username, :username_canonical, NULL, NULL, :password_hash, :display_name, 1, 1, 1, NULL, :created_at, :updated_at, NULL, NULL, :creation_reason, 'approved', NULL, :approved_at)"
    );
    $insert->execute([
        'username' => $usernameCanonical,
        'username_canonical' => $usernameCanonical,
        'password_hash' => password_hash($oldPassword, PASSWORD_DEFAULT),
        'display_name' => 'Required Password Check',
        'created_at' => $now,
        'updated_at' => $now,
        'creation_reason' => 'Автоматизированная проверка обязательной смены пароля.',
        'approved_at' => $now,
    ]);
    $userId = (int) $pdo->lastInsertId();
    $service = new RequiredPasswordChangeService($pdo);

    $wrongCurrent = $service->change($userId, [
        'current_password' => 'WrongPassword123',
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ]);
    check_condition(!$wrongCurrent['ok'] && isset($wrongCurrent['errors']['current_password']), 'Неверный текущий пароль не был отклонен.');

    $weakPassword = $service->change($userId, [
        'current_password' => $oldPassword,
        'new_password' => 'weak',
        'new_password_confirmation' => 'weak',
    ]);
    check_condition(!$weakPassword['ok'] && isset($weakPassword['errors']['new_password']), 'Слабый пароль не был отклонен.');

    $mismatch = $service->change($userId, [
        'current_password' => $oldPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => 'AnotherPass9876',
    ]);
    check_condition(!$mismatch['ok'] && isset($mismatch['errors']['new_password_confirmation']), 'Несовпадающее подтверждение не было отклонено.');

    $samePassword = $service->change($userId, [
        'current_password' => $oldPassword,
        'new_password' => $oldPassword,
        'new_password_confirmation' => $oldPassword,
    ]);
    check_condition(!$samePassword['ok'] && isset($samePassword['errors']['new_password']), 'Повторное использование текущего пароля не было отклонено.');

    $success = $service->change($userId, [
        'current_password' => $oldPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ]);
    check_condition($success['ok'], 'Корректная смена пароля завершилась ошибкой.');

    $select = $pdo->prepare('SELECT password_hash, is_temporary, must_change_password FROM users WHERE id = :id');
    $select->execute(['id' => $userId]);
    $updated = $select->fetch();
    check_condition((bool) $updated, 'Тестовая учетная запись не найдена после обновления.');
    check_condition(!password_verify($oldPassword, (string) $updated['password_hash']), 'Старый пароль продолжает проходить проверку.');
    check_condition(password_verify($newPassword, (string) $updated['password_hash']), 'Новый пароль не проходит проверку.');
    check_condition((int) $updated['must_change_password'] === 0, 'Флаг must_change_password не снят.');
    check_condition((int) $updated['is_temporary'] === 0, 'Флаг is_temporary не снят.');

    echo "OK wrong current password rejected\n";
    echo "OK weak password rejected\n";
    echo "OK confirmation mismatch rejected\n";
    echo "OK current password reuse rejected\n";
    echo "OK password hash changed\n";
    echo "OK temporary flags cleared\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка проверки обязательной смены пароля: ' . $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        try {
            if ($userId !== null) {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $delete->execute(['id' => $userId]);
            } else {
                $delete = $pdo->prepare('DELETE FROM users WHERE username_canonical = :username');
                $delete->execute(['username' => $usernameCanonical]);
            }
        } catch (Throwable $cleanupException) {
            fwrite(STDERR, 'Ошибка очистки тестовой учетной записи: ' . $cleanupException->getMessage() . PHP_EOL);
            $exitCode = 1;
        }
    }
}

exit($exitCode);
