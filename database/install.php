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
    fwrite(STDERR, "Не найден config/local.php. Скопируйте config/local.example.php.\n");
    exit(1);
}
$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];

try {
    $serverDsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset']);
    $server = new PDO($serverDsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $databaseName = str_replace('`', '``', (string) $db['name']);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migrationName = '001_starter_security.sql';
    $sql = file_get_contents(__DIR__ . '/migrations/' . $migrationName);
    if ($sql === false) {
        throw new RuntimeException('Не удалось прочитать файл миграции.');
    }

    $pdo->exec($sql);
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, :applied_at)');
    $stmt->execute(['migration' => $migrationName, 'applied_at' => $now]);

    $pdo->beginTransaction();
    $roleStmt = $pdo->prepare('INSERT INTO roles (code, name, description, is_system, created_at, updated_at) VALUES (:code, :name, :description, 1, :created_at, :updated_at) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), is_system = 1, updated_at = VALUES(updated_at)');
    $roleStmt->execute([
        'code' => 'system_owner',
        'name' => 'Владелец системы',
        'description' => 'Главная системная роль с абсолютными правами.',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $permissionStmt = $pdo->prepare('INSERT INTO permissions (code, name, description, is_system, created_at, updated_at) VALUES (:code, :name, :description, 1, :created_at, :updated_at) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), is_system = 1, updated_at = VALUES(updated_at)');
    $permissionStmt->execute([
        'code' => 'system.*.*',
        'name' => 'Абсолютный системный доступ',
        'description' => 'Полный доступ ко всем ресурсам и действиям системы.',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $pdo->exec("INSERT INTO role_permissions (role_id, permission_id, assigned_at)
        SELECT r.id, p.id, " . $pdo->quote($now) . " FROM roles r CROSS JOIN permissions p
        WHERE r.code = 'system_owner' AND p.code = 'system.*.*'
        ON DUPLICATE KEY UPDATE assigned_at = assigned_at");

    $settingStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) VALUES ('installation_completed', '0', :created_at, :updated_at) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)");
    $settingStmt->execute(['created_at' => $now, 'updated_at' => $now]);
    $pdo->commit();

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "База данных: {$db['name']}\n";
    echo "Миграция: {$migrationName}\n";
    echo "Пользователей: {$userCount}\n";
    echo $userCount === 0 ? "Первичная регистрация доступна.\n" : "Первичная регистрация отключена.\n";
    exit(0);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Ошибка установки: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
