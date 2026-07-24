<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

if ((string) app_config('environment') !== 'local') {
    fwrite(STDERR, "Локальный владелец может создаваться только в environment=local.\n");
    exit(1);
}

try {
    $userCount = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount > 0 || installation_completed()) {
        echo "Пользователь не создан: первичная установка уже завершена.\n";
        exit(0);
    }

    $result = bootstrap_owner_service()->createOwner(
        username: 'Admin',
        displayName: 'Admin',
        password: '12315',
        email: null,
        isTemporary: true,
        mustChangePassword: true
    );

    echo "Создан временный локальный владелец.\n";
    echo "Username: Admin\n";
    echo "Role: system_owner\n";
    echo "User ID: {$result['user_id']}\n";
    echo "При первом входе пароль необходимо заменить.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Ошибка создания локального владельца: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
