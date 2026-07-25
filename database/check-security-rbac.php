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

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $errors = [];

    $migration = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migration->execute(['migration' => '002_security_users_management.sql']);
    if ((int) $migration->fetchColumn() !== 1) {
        $errors[] = 'Миграция 002 не зарегистрирована.';
    }

    foreach (['user_roles', 'role_permissions'] as $table) {
        $column = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns '
            . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
        );
        $column->execute([
            'schema_name' => $db['name'],
            'table_name' => $table,
            'column_name' => 'assigned_by',
        ]);
        if ((int) $column->fetchColumn() !== 1) {
            $errors[] = "В таблице {$table} отсутствует assigned_by.";
        }
    }

    $requiredRoles = ['system_owner', 'administrator', 'operator', 'viewer'];
    $roleQuery = $pdo->query('SELECT code FROM roles WHERE is_system = 1');
    $actualRoles = $roleQuery->fetchAll(PDO::FETCH_COLUMN);
    foreach ($requiredRoles as $role) {
        if (!in_array($role, $actualRoles, true)) {
            $errors[] = "Отсутствует системная роль {$role}.";
        }
    }

    $permissionCount = (int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE is_system = 1")->fetchColumn();
    if ($permissionCount < 19) {
        $errors[] = "Недостаточно системных разрешений: {$permissionCount}.";
    }

    $ownerPermission = (int) $pdo->query(
        "SELECT COUNT(*) FROM role_permissions rp "
        . "JOIN roles r ON r.id = rp.role_id "
        . "JOIN permissions p ON p.id = rp.permission_id "
        . "WHERE r.code = 'system_owner' AND p.code = 'system.*.*'"
    )->fetchColumn();
    if ($ownerPermission !== 1) {
        $errors[] = 'Роли system_owner не назначено system.*.*.';
    }

    $ownerUsers = (int) $pdo->query(
        "SELECT COUNT(*) FROM users u "
        . "JOIN user_roles ur ON ur.user_id = u.id "
        . "JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' AND u.deleted_at IS NULL"
    )->fetchColumn();
    if ($ownerUsers < 1) {
        $errors[] = 'Не найден действующий владелец системы.';
    }

    if ($errors !== []) {
        foreach ($errors as $error) {
            fwrite(STDERR, "ERROR {$error}\n");
        }
        exit(1);
    }

    echo "OK migration 002\n";
    echo 'OK system roles: ' . count($requiredRoles) . "\n";
    echo "OK system permissions: {$permissionCount}\n";
    echo "OK active owners: {$ownerUsers}\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка проверки RBAC: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
