<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require dirname(__DIR__) . '/app/bootstrap.php';
    $pdo = db();
    $databaseVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $migrationCount = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();

    echo json_encode([
        'status' => 'ok',
        'application' => [
            'name' => (string) app_config('name'),
            'version' => (string) app_config('version'),
            'environment' => (string) app_config('environment'),
            'php' => PHP_VERSION,
        ],
        'database' => [
            'status' => 'connected',
            'name' => $databaseName,
            'version' => $databaseVersion,
            'migrations' => $migrationCount,
        ],
        'installation_completed' => installation_completed(),
        'checked_at' => date(DATE_ATOM),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);
    $debug = function_exists('app_config') && app_config('debug', false) === true;
    echo json_encode([
        'status' => 'error',
        'message' => $debug ? $exception->getMessage() : 'Приложение или база данных недоступны.',
        'checked_at' => date(DATE_ATOM),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
