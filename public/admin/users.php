<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_system_owner();

$counts = [];
foreach (['users', 'roles', 'permissions'] as $table) {
    $counts[$table] = (int) db()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}

$registrationStatus = installation_completed() ? 'Отключена' : 'Доступна только первичная регистрация';
$modules = [
    ['Пользователи системы', 'Создание, блокировка и сопровождение учетных записей.'],
    ['Роли', 'Управление ролями системы.'],
    ['Разрешения', 'Каталог разрешений и политик доступа.'],
    ['Назначение ролей', 'Связь пользователей и ролей.'],
    ['Матрица доступа', 'Сводное представление ролей и разрешений.'],
    ['Сеансы и безопасность', 'Контроль активных сеансов и параметров учетных записей.'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Пользователи</h1><p class="site-description">Учетные записи, роли и разрешения</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="stats-grid" aria-label="Сводка безопасности">
<article class="stat-tile glass-tile"><span>Пользователи</span><strong><?= $counts['users'] ?></strong></article>
<article class="stat-tile glass-tile"><span>Роли</span><strong><?= $counts['roles'] ?></strong></article>
<article class="stat-tile glass-tile"><span>Разрешения</span><strong><?= $counts['permissions'] ?></strong></article>
<article class="stat-tile glass-tile"><span>Регистрация</span><strong class="stat-text"><?= e($registrationStatus) ?></strong></article>
</section>
<section class="module-grid" aria-label="Управление пользователями">
<?php foreach ($modules as [$title, $description]): ?>
<article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
