<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_system_owner();

$directories = [
    ['Подразделения', 'Справочник подразделений воинской части.'],
    ['Воинские звания', 'Справочник воинских званий военнослужащих.'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочники — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Справочники</h1><p class="site-description">Системные и предметные классификаторы</p></div><a class="secondary-button" href="/admin/content.php">К контенту</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="section-intro glass-tile"><div><strong><?= e($user['display_name']) ?></strong><span>Выберите справочник для просмотра и управления данными. Создание и редактирование справочников будет добавлено в следующих инкрементах.</span></div></section>
<section class="module-grid" aria-label="Доступные справочники">
<?php foreach ($directories as [$title, $description]): ?>
<article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
