<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_system_owner();

$dbStatus = 'Подключено';
$dbVersion = '—';
try {
    $dbVersion = (string) db()->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable) {
    $dbStatus = 'Ошибка';
}

$migrationCount = (int) db()->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
$lastMigration = db()->query('SELECT MAX(applied_at) FROM migrations')->fetchColumn();
$database = app_config('database');
$diagnostics = [
    ['Версия приложения', (string) app_config('version')],
    ['Окружение', (string) app_config('environment')],
    ['PHP', PHP_VERSION],
    ['MySQL', $dbStatus . ($dbVersion !== '—' ? ' · ' . $dbVersion : '')],
    ['База данных', (string) $database['name']],
    ['Серверное время', date('Y-m-d H:i:s')],
    ['Часовой пояс', date_default_timezone_get()],
    ['Активная тема', (string) app_config('theme', 'asu-blue')],
    ['Debug', app_config('debug', false) ? 'Включен' : 'Отключен'],
    ['Применено миграций', (string) $migrationCount],
    ['Последняя миграция', is_string($lastMigration) && $lastMigration !== '' ? $lastMigration : 'Нет данных'],
];

$modules = [
    ['Общие параметры системы', 'Глобальные настройки экземпляра системы.'],
    ['Темы оформления', 'Выбор и настройка визуальной темы.'],
    ['Параметры окружения', 'Режим работы и инфраструктурные параметры.'],
    ['База данных', 'Подключение, состояние и операции обслуживания.'],
    ['Миграции', 'История и управление изменениями схемы БД.'],
    ['Диагностика', 'Проверки состояния приложения.'],
    ['Обслуживание системы', 'Регламентные операции.'],
    ['Резервное копирование', 'Создание и восстановление резервных копий.'],
    ['Журналы приложения', 'Просмотр технических журналов.'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Настройки системы</h1><p class="site-description">Параметры приложения и инфраструктуры</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="diagnostic-grid" aria-label="Диагностика системы">
<?php foreach ($diagnostics as [$label, $value]): ?>
<article class="diagnostic-row glass-tile"><span><?= e($label) ?></span><strong><?= e($value) ?></strong></article>
<?php endforeach; ?>
</section>
<section class="module-grid" aria-label="Системные настройки">
<?php foreach ($modules as [$title, $description]): ?>
<article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
