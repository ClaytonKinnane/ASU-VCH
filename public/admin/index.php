<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_system_owner();

$roleStmt = db()->prepare(
    'SELECT r.name FROM roles r '
    . 'JOIN user_roles ur ON ur.role_id = r.id '
    . 'WHERE ur.user_id = :user_id ORDER BY r.name'
);
$roleStmt->execute(['user_id' => (int) $user['id']]);
$roleNames = array_values(array_filter(
    array_map(static fn (array $row): string => (string) ($row['name'] ?? ''), $roleStmt->fetchAll()),
    static fn (string $name): bool => $name !== ''
));
$roleSummary = $roleNames !== [] ? implode(', ', $roleNames) : 'Роль не назначена';

$dbStatus = 'Подключено';
try {
    db()->query('SELECT 1');
} catch (Throwable) {
    $dbStatus = 'Ошибка';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Панель администратора</h1><p class="site-description">АСУ-ВЧ · <?= e((string) app_config('version')) ?></p></div><form class="admin-logout" method="post" action="/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="secondary-button" type="submit">Выйти</button></form></div></div></header>
<main class="admin-main"><div class="container">
<section class="admin-summary glass-tile">
    <div><strong><?= e((string) $user['display_name']) ?></strong><span>@<?= e((string) $user['username']) ?> · <?= e($roleSummary) ?></span></div>
    <div><strong>Приложение</strong><span>PHP <?= e(PHP_VERSION) ?> · MySQL: <?= e($dbStatus) ?></span></div>
    <?php if ((int) $user['is_temporary'] === 1): ?><div class="warning-badge">Временная тестовая учетная запись</div><?php endif; ?>
</section>
<section class="dashboard-grid" aria-label="Основные разделы">
    <a class="dashboard-tile glass-tile" href="/admin/content.php"><span class="tile-kicker">Раздел</span><h2>Контент</h2><p>Прикладные модули, справочники, структура, военнослужащие, документы и будущие бизнес-домены.</p><span class="tile-action">Открыть раздел →</span></a>
    <a class="dashboard-tile glass-tile" href="/admin/users.php"><span class="tile-kicker">Раздел</span><h2>Пользователи</h2><p>Учетные записи, роли, разрешения, назначения ролей и состояние первичной регистрации.</p><span class="tile-action">Открыть раздел →</span></a>
    <a class="dashboard-tile glass-tile" href="/admin/settings.php"><span class="tile-kicker">Раздел</span><h2>Настройки системы</h2><p>Окружение, темы, база данных, миграции, диагностика и обслуживание приложения.</p><span class="tile-action">Открыть раздел →</span></a>
</section>
</div></main>
</body>
</html>
