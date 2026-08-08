<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_authenticated_user();
$isOwner = in_array('system_owner', current_user_role_codes(), true);
$canViewMilitaryPositions = has_permission('directories.military_positions.view');
if (!$isOwner && !$canViewMilitaryPositions) {
    require_permission('directories.military_positions.view');
}

$directories = [];
if ($isOwner) {
    $directories = [
    [
        'Организационные элементы и подразделения',
        'Классификатор типов органов военного управления, объединений, соединений, воинских частей, организаций и подразделений.',
        '/admin/directories/organizational-elements.php',
    ],
    [
        'Составы военнослужащих и воинские звания',
        'Нормативный перечень составов военнослужащих, войсковых и корабельных воинских званий.',
        '/admin/directories/military-ranks.php',
    ],
    [
        'Военно-учётные специальности',
        'Публичные сведения, структура кодов и официальные программы подготовки.',
        '/admin/directories/military-occupational-specialties.php',
    ],
    ];
}
if ($isOwner || $canViewMilitaryPositions) {
    $directories[] = [
        'Воинские должности',
        'Единый версионируемый справочник канонических наименований воинских должностей.',
        '/admin/directories/military-positions.php',
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочники — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Справочники</h1><p class="site-description">Системные и предметные классификаторы</p></div><a class="secondary-button" href="/admin/content.php">К контенту</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="section-intro glass-tile"><div><strong><?= e($user['display_name']) ?></strong><span>Отображаются только справочники, разрешённые вашей учетной записи.</span></div></section>
<section class="module-grid" aria-label="Доступные справочники">
<?php foreach ($directories as [$title, $description, $href]): ?>
<a class="dashboard-tile module-tile glass-tile directory-link-tile" href="<?= e($href) ?>"><span class="tile-kicker">Доступно</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p><span class="tile-action">Открыть →</span></a>
<?php endforeach; ?>
</section>
</div></main>
</body>
</html>
