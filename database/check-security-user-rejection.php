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

require_once $root . '/app/Security/UserApprovalService.php';
require_once $root . '/app/Security/UserRejectionService.php';

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];
$userId = null;
$usernameCanonical = 'rejection_check_' . bin2hex(random_bytes(6));
$exitCode = 0;

function rejection_check_condition(bool $condition, string $message): void
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

    $migration = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migration->execute(['migration' => '004_security_user_rejection_audit.sql']);
    rejection_check_condition((int) $migration->fetchColumn() === 1, 'Миграция 004 не зарегистрирована.');

    $column = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    foreach (['rejected_by', 'rejected_at', 'rejection_reason'] as $columnName) {
        $column->execute([
            'schema_name' => $db['name'],
            'table_name' => 'users',
            'column_name' => $columnName,
        ]);
        rejection_check_condition((int) $column->fetchColumn() === 1, "В таблице users отсутствует {$columnName}.");
    }

    $foreignKey = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.referential_constraints '
        . 'WHERE constraint_schema = :schema_name AND constraint_name = :constraint_name '
        . "AND delete_rule = 'SET NULL'"
    );
    $foreignKey->execute([
        'schema_name' => $db['name'],
        'constraint_name' => 'fk_users_rejected_by',
    ]);
    rejection_check_condition((int) $foreignKey->fetchColumn() === 1, 'Не найден fk_users_rejected_by с ON DELETE SET NULL.');

    $permissionCount = (int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE is_system = 1")->fetchColumn();
    rejection_check_condition($permissionCount === 19, "Ожидалось 19 системных разрешений, найдено {$permissionCount}.");

    $administratorPermission = (int) $pdo->query(
        "SELECT COUNT(*) FROM role_permissions rp "
        . "JOIN roles r ON r.id = rp.role_id "
        . "JOIN permissions p ON p.id = rp.permission_id "
        . "WHERE r.code = 'administrator' AND p.code = 'security.users.reject'"
    )->fetchColumn();
    rejection_check_condition($administratorPermission === 1, 'Роли administrator не назначено security.users.reject.');

    $actorId = (int) $pdo->query(
        "SELECT u.id FROM users u "
        . "JOIN user_roles ur ON ur.user_id = u.id "
        . "JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' AND u.is_active = 1 "
        . "AND u.approval_status = 'approved' AND u.deleted_at IS NULL "
        . 'ORDER BY u.id LIMIT 1'
    )->fetchColumn();
    rejection_check_condition($actorId > 0, 'Не найден действующий владелец для проверки аудита.');

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $insert = $pdo->prepare(
        'INSERT INTO users (username, username_canonical, email, email_canonical, password_hash, display_name, is_active, is_temporary, must_change_password, last_login_at, created_at, updated_at, deleted_at, created_by, creation_reason, approval_status, approved_by, approved_at) '
        . "VALUES (:username, :username_canonical, NULL, NULL, :password_hash, :display_name, 0, 0, 0, NULL, :created_at, :updated_at, NULL, :created_by, :creation_reason, 'pending', NULL, NULL)"
    );
    $insert->execute([
        'username' => $usernameCanonical,
        'username_canonical' => $usernameCanonical,
        'password_hash' => password_hash('RejectionCheck123', PASSWORD_DEFAULT),
        'display_name' => 'User Rejection Check',
        'created_at' => $now,
        'updated_at' => $now,
        'created_by' => $actorId,
        'creation_reason' => 'Автоматизированная проверка отклонения учетной записи.',
    ]);
    $userId = (int) $pdo->lastInsertId();

    $rejectionService = new UserRejectionService($pdo);
    $approvalService = new UserApprovalService($pdo);

    $shortReason = $rejectionService->reject($userId, $actorId, 'Коротко');
    rejection_check_condition(
        !$shortReason['ok'] && isset($shortReason['errors']['reason']),
        'Короткое основание отклонения не было отклонено.'
    );

    $reason = "Заявка не соответствует утвержденному основанию доступа.\nТребуется повторное согласование.";
    $success = $rejectionService->reject($userId, $actorId, $reason);
    rejection_check_condition($success['ok'], 'Корректное отклонение завершилось ошибкой.');

    $select = $pdo->prepare(
        'SELECT approval_status, is_active, approved_by, approved_at, rejected_by, rejected_at, rejection_reason '
        . 'FROM users WHERE id = :id'
    );
    $select->execute(['id' => $userId]);
    $updated = $select->fetch();
    rejection_check_condition((bool) $updated, 'Тестовая учетная запись не найдена после отклонения.');
    rejection_check_condition($updated['approval_status'] === 'rejected', 'Статус rejected не установлен.');
    rejection_check_condition((int) $updated['is_active'] === 0, 'Отклоненная учетная запись осталась активной.');
    rejection_check_condition($updated['approved_by'] === null && $updated['approved_at'] === null, 'Поля подтверждения не очищены.');
    rejection_check_condition((int) $updated['rejected_by'] === $actorId, 'Субъект отклонения записан неверно.');
    rejection_check_condition($updated['rejected_at'] !== null, 'Дата отклонения не записана.');
    rejection_check_condition($updated['rejection_reason'] === $reason, 'Основание отклонения записано неверно.');

    $repeat = $rejectionService->reject($userId, $actorId, $reason);
    rejection_check_condition(
        !$repeat['ok'] && ($repeat['error'] ?? null) === 'Учетная запись уже обработана.',
        'Повторное отклонение не было запрещено.'
    );

    $approveRejected = $approvalService->approve($userId, $actorId);
    rejection_check_condition(
        !$approveRejected['ok'] && ($approveRejected['error'] ?? null) === 'Учетная запись уже обработана.',
        'Подтверждение отклоненной учетной записи не было запрещено.'
    );

    echo "OK migration 004\n";
    echo "OK rejection columns: 3\n";
    echo "OK rejection foreign key\n";
    echo "OK system permissions: {$permissionCount}\n";
    echo "OK administrator rejection permission\n";
    echo "OK short rejection reason rejected\n";
    echo "OK pending user rejected\n";
    echo "OK rejection audit recorded\n";
    echo "OK repeated rejection rejected\n";
    echo "OK approval after rejection rejected\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка проверки отклонения пользователей: ' . $exception->getMessage() . PHP_EOL);
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
