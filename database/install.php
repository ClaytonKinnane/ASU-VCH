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

    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    if ($migrationFiles === false || $migrationFiles === []) {
        throw new RuntimeException('Файлы миграций не найдены.');
    }
    sort($migrationFiles, SORT_NATURAL | SORT_FLAG_CASE);

    $appliedNow = [];
    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);

        if ($migrationName !== '001_starter_security.sql') {
            $check = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
            $check->execute(['migration' => $migrationName]);
            if ((int) $check->fetchColumn() > 0) {
                continue;
            }
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException("Не удалось прочитать миграцию: {$migrationName}");
        }

        $pdo->exec($sql);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $record = $pdo->prepare('INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, :applied_at)');
        $record->execute(['migration' => $migrationName, 'applied_at' => $now]);
        $appliedNow[] = $migrationName;
    }

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $settingStmt = $pdo->prepare(
        "INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) "
        . "VALUES ('installation_completed', '0', :created_at, :updated_at) "
        . "ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)"
    );
    $settingStmt->execute(['created_at' => $now, 'updated_at' => $now]);

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $migrationCount = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();

    echo "База данных: {$db['name']}\n";
    echo 'Применено миграций: ' . $migrationCount . "\n";
    if ($appliedNow === []) {
        echo "Новых миграций нет.\n";
    } else {
        foreach ($appliedNow as $migrationName) {
            echo "Применена миграция: {$migrationName}\n";
        }
    }
    echo "Пользователей: {$userCount}\n";
    echo $userCount === 0 ? "Первичная регистрация доступна.\n" : "Первичная регистрация отключена.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка установки: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
