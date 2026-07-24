<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_system_owner();

$modules = [
    ['Справочники', 'Системные и предметные классификаторы.'],
    ['Организационная структура', 'Воинские части, подразделения и подчиненность.'],
    ['Военнослужащие', 'Карточки и учет личного состава.'],
    ['Документы', 'Регистрация, хранение и движение документов.'],
    ['Приказы', 'Подготовка и учет приказов.'],
    ['Медицинский учет', 'Медицинские сведения в пределах утвержденной архитектуры.'],
    ['Имущество и оборудование', 'Учет материальных средств и оборудования.'],
    ['Транспорт', 'Учет транспортных средств.'],
    ['Аудит и журнал событий', 'Просмотр значимых событий системы.'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контент — АСУ-ВЧ</title>
    <link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Контент</h1><p class="site-description">Прикладные и предметные модули системы</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="section-intro glass-tile"><div><strong><?= e($user['display_name']) ?></strong><span>Раздел доступен владельцу системы. Вложенные модули пока не реализованы.</span></div></section>
<section class="module-grid" aria-label="Модули контента">
<?php foreach ($modules as [$title, $description]): ?>
<article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
