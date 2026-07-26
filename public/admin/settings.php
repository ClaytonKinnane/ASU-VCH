<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_permission('system.settings.view');

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
$debugEnabled = (bool) app_config('debug', false);

$diagnosticColumns = [
    [
        ['Версия приложения', (string) app_config('version')],
        ['PHP', PHP_VERSION],
        ['MySQL', $dbVersion, $dbStatus === 'Подключено' ? 'success' : 'error', $dbStatus],
        ['База данных', (string) $database['name']],
        ['Окружение', (string) app_config('environment')],
        ['Часовой пояс', date_default_timezone_get()],
    ],
    [
        ['Активная тема', active_theme_name() . ' (' . active_theme() . ')'],
        ['Debug', '', $debugEnabled ? 'success' : 'muted', $debugEnabled ? 'Включен' : 'Отключен'],
        ['Применено миграций', (string) $migrationCount],
        ['Последняя миграция', is_string($lastMigration) && $lastMigration !== '' ? $lastMigration : 'Нет данных'],
        ['Серверное время', date('Y-m-d H:i:s')],
    ],
];

$modules = [
    ['Общие параметры системы', 'Глобальные настройки экземпляра системы.', null],
    ['Темы оформления', 'Выбор глобальной визуальной темы АСУ-ВЧ.', '/admin/settings/themes.php'],
    ['Параметры окружения', 'Режим работы и инфраструктурные параметры.', null],
    ['База данных', 'Подключение, состояние и операции обслуживания.', null],
    ['Миграции', 'История и управление изменениями схемы БД.', null],
    ['Диагностика', 'Проверки состояния приложения.', null],
    ['Обслуживание системы', 'Регламентные операции.', null],
    ['Резервное копирование', 'Создание и восстановление резервных копий.', null],
    ['Журналы приложения', 'Просмотр технических журналов.', null],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme-management.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Настройки системы</h1><p class="site-description">Параметры приложения и инфраструктуры</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="technical-panel glass-tile" aria-labelledby="technical-information-title">
    <h2 id="technical-information-title" class="technical-panel-title">Техническая информация</h2>
    <div class="technical-columns">
        <?php foreach ($diagnosticColumns as $column): ?>
        <dl class="technical-list">
            <?php foreach ($column as $item): ?>
                <?php [$label, $value] = $item; $badgeType = $item[2] ?? null; $badgeText = $item[3] ?? null; ?>
                <div class="technical-row">
                    <dt><?= e($label) ?></dt>
                    <dd>
                        <?php if ($value !== ''): ?><strong><?= e($value) ?></strong><?php endif; ?>
                        <?php if (is_string($badgeText)): ?><span class="state-badge state-badge--<?= e((string) $badgeType) ?>"><?= e($badgeText) ?></span><?php endif; ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <?php endforeach; ?>
    </div>
</section>
<section class="module-grid" aria-label="Системные настройки">
<?php foreach ($modules as [$title, $description, $href]): ?>
<?php if (is_string($href)): ?><a class="module-tile glass-tile settings-link-tile" href="<?= e($href) ?>"><span class="tile-kicker">Доступно</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p><span class="tile-action">Открыть →</span></a>
<?php else: ?><article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article><?php endif; ?>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
