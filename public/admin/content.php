<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = require_authenticated_user();
$isOwner = in_array('system_owner', current_user_role_codes(), true);
$canViewStructure = has_permission('organization.structures.view');
$canViewStaffing = has_permission('staffing.registers.view');
$canViewMilitaryPositions = has_permission('directories.military_positions.view');
if (!$isOwner && !$canViewStructure && !$canViewStaffing && !$canViewMilitaryPositions) {
    require_permission('directories.military_positions.view');
}

$futureModules = [
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
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Контент</h1><p class="site-description">Прикладные и предметные модули системы</p></div><a class="secondary-button" href="/admin/">К панели</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="section-intro glass-tile"><div><strong><?= e($user['display_name']) ?></strong><span>Отображаются только модули, разрешённые вашей учетной записи.</span></div></section>
<section class="module-grid" aria-label="Модули контента">
<?php if ($isOwner || $canViewMilitaryPositions): ?><a class="dashboard-tile module-tile glass-tile" href="/admin/directories.php"><h2>Справочники</h2><p>Системные и предметные классификаторы.</p><span class="tile-action">Открыть →</span></a><?php endif; ?>
<?php if ($canViewStructure): ?><a class="dashboard-tile module-tile glass-tile" href="/admin/organization/structures.php"><h2>Организационная структура</h2><p>Воинские части, подразделения, версии и основная постоянная подчинённость.</p><span class="tile-action">Открыть →</span></a><?php endif; ?>
<?php if ($canViewStaffing): ?><a class="dashboard-tile module-tile glass-tile" href="/admin/staffing/registers.php"><h2>Штатная структура</h2><p>Версионные штатные реестры, документы-основания и индивидуальные нормативные позиции.</p><span class="tile-action">Открыть →</span></a><?php endif; ?>
<?php if ($isOwner): ?><?php foreach ($futureModules as [$title, $description]): ?><article class="module-tile glass-tile is-disabled"><span class="status-badge">В разработке</span><h2><?= e($title) ?></h2><p><?= e($description) ?></p></article><?php endforeach; ?><?php endif; ?>
</section>
</div></main>
</body>
</html>
