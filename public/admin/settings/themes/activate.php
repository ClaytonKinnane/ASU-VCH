<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
$user = require_permission('system.settings.update');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/settings/themes.php');
}

require_csrf();
$slug = $_POST['theme'] ?? null;

try {
    if (!is_string($slug)) {
        throw new InvalidArgumentException('Выбранная тема недоступна.');
    }
    theme_activation_service()->activate($slug, (int) $user['id']);
    $token = create_operation_result('success', 'Тема оформления активирована.');
} catch (InvalidArgumentException) {
    $token = create_operation_result('error', 'Выбранная тема недоступна.');
} catch (Throwable $exception) {
    error_log('Theme activation failed.');
    $token = create_operation_result('error', 'Не удалось активировать тему оформления.');
}

redirect('/admin/settings/themes.php?result=' . rawurlencode($token));
