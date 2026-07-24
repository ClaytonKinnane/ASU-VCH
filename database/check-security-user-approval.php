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
    $migration->execute(['migration' => '003_security_user_approval.sql']);
    if ((int) $migration->fetchColumn() !== 1) {
        $errors[] = 'Миграция 003 не зарегистрирована.';
    }

    $requiredColumns = [
        'created_by',
        'creation_reason',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    $column = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    foreach ($requiredColumns as $columnName) {
        $column->execute([
            'schema_name' => $db['name'],
            'table_name' => 'users',
            'column_name' => $columnName,
        ]);
        if ((int) $column->fetchColumn() !== 1) {
            $errors[] = "В таблице users отсутствует {$columnName}.";
        }
    }

    $foreignKeys = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.referential_constraints '
        . 'WHERE constraint_schema = :schema_name AND constraint_name = :constraint_name '
        . "AND delete_rule = 'SET NULL'"
    );
    foreach (['fk_users_created_by', 'fk_users_approved_by'] as $constraintName) {
        $foreignKeys->execute([
            'schema_name' => $db['name'],
            'constraint_name' => $constraintName,
        ]);
        if ((int) $foreignKeys->fetchColumn() !== 1) {
            $errors[] = "Не найден внешний ключ {$constraintName} с ON DELETE SET NULL.";
        }
    }

    $invalidStatuses = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE approval_status NOT IN ('pending', 'approved', 'rejected')"
    )->fetchColumn();
    if ($invalidStatuses !== 0) {
        $errors[] = "Найдены учетные записи с недопустимым approval_status: {$invalidStatuses}.";
    }

    $legacyApproved = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE approval_status = 'approved'"
    )->fetchColumn();
    if ($legacyApproved < 1) {
        $errors[] = 'Не найдено ни одной подтвержденной учетной записи.';
    }

    $activeOwners = (int) $pdo->query(
        "SELECT COUNT(*) FROM users u "
        . "JOIN user_roles ur ON ur.user_id = u.id "
        . "JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' "
        . "AND u.deleted_at IS NULL AND u.is_active = 1 AND u.approval_status = 'approved'"
    )->fetchColumn();
    if ($activeOwners < 1) {
        $errors[] = 'Не найден активный подтвержденный владелец системы.';
    }

    if ($errors !== []) {
        foreach ($errors as $error) {
            fwrite(STDERR, "ERROR {$error}\n");
        }
        exit(1);
    }

    echo "OK migration 003\n";
    echo 'OK approval columns: ' . count($requiredColumns) . "\n";
    echo "OK approved users: {$legacyApproved}\n";
    echo "OK active approved owners: {$activeOwners}\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка проверки подтверждения пользователей: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
