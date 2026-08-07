<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

$user = require_permission('directories.military_positions.view');
$repository = military_position_catalog_repository();
$versions = $repository->versions();
$current = $repository->currentVersion();
$hasDraft = $repository->hasDraft();
$canManage = has_permission('directories.military_positions.manage');
$canViewHistory = has_permission('directories.military_positions.history');
$success = flash('success');
$error = flash('military_positions_error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Воинские должности — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Воинские должности</h1><p class="site-description">Управляемый версионируемый справочник канонических наименований</p></div>
    <a class="secondary-button" href="/admin/directories.php">К справочникам</a>
</div></div></header>
<main class="admin-main"><div class="container military-position-layout">
    <?php if ($success !== null): ?><div class="form-message is-success is-visible"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="form-message is-error is-visible"><?= e($error) ?></div><?php endif; ?>

    <section class="directory-hero glass-tile military-position-hero">
        <div class="directory-hero-heading"><div><h2>Версии справочника</h2><p>Исторические версии сохраняются без скрытого изменения ссылок штатной структуры.</p></div>
        <div class="military-position-actions"><?php if ($canViewHistory): ?><a class="secondary-button" href="/admin/directories/military-positions/history.php">История</a><?php endif; ?></div></div>
        <dl class="directory-meta"><div><dt>Текущая версия</dt><dd>№ <?= (int) $current['version_number'] ?> · <?= e((string) $current['version_label']) ?></dd></div><div><dt>Должностей</dt><dd><?= (int) $current['entry_count'] ?></dd></div><div><dt>Черновик</dt><dd><?= $hasDraft ? 'Есть' : 'Нет' ?></dd></div></dl>
    </section>

    <?php if ($canManage && !$hasDraft): ?>
    <details class="military-position-panel glass-tile">
        <summary>Создать новую черновую версию</summary>
        <form method="post" action="/admin/directories/military-positions/versions/create.php" class="military-position-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="expected_catalog_revision" value="<?= (int) $current['revision'] ?>">
            <input type="hidden" name="return_to" value="/admin/directories/military-positions.php">
            <label>Название версии<input name="version_label" maxlength="255" required></label>
            <label>Дата начала действия<input type="date" name="effective_from" required></label>
            <label class="span-2">Основание создания<textarea name="change_reason" maxlength="1000" required></textarea></label>
            <div class="span-2"><button class="primary-button" type="submit">Создать черновик</button></div>
        </form>
    </details>
    <?php endif; ?>

    <section class="military-position-version-list" aria-label="Версии справочника">
        <?php foreach ($versions as $version): ?>
            <?php require __DIR__ . '/military-positions/views/version-card.php'; ?>
        <?php endforeach; ?>
    </section>
</div></main>
</body>
</html>
