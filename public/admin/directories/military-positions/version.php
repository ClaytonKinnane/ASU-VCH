<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

$user = require_permission('directories.military_positions.view');
$repository = military_position_catalog_repository();
$rawVersionId = $_GET['id'] ?? null;
$version = (is_string($rawVersionId) && preg_match('/\A[1-9][0-9]*\z/D', $rawVersionId) === 1)
    ? $repository->version((int) $rawVersionId)
    : $repository->defaultVersion();
$versionId = (int) $version['id'];
$query = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 150, 'UTF-8') : '';
$status = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['active','archived'], true) ? $_GET['status'] : '';
$combined = is_string($_GET['is_combined'] ?? null) && in_array($_GET['is_combined'], ['0','1'], true) ? $_GET['is_combined'] : '';
$sourceType = is_string($_GET['source_type'] ?? null) && in_array($_GET['source_type'], ['official','local','imported'], true) ? $_GET['source_type'] : '';
$result = $repository->entries($versionId, $query, $status, $combined, $sourceType);
$sources = $repository->versionSources($versionId);
$canManage = has_permission('directories.military_positions.manage') && $version['status'] === 'draft';
$canPublish = has_permission('directories.military_positions.publish') && $version['status'] === 'draft';
$canViewHistory = has_permission('directories.military_positions.history');
$success = flash('success');
$error = flash('military_positions_error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $version['version_label']) ?> — Воинские должности</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Воинские должности</h1><p class="site-description">Версия № <?= (int) $version['version_number'] ?></p></div>
    <a class="secondary-button" href="/admin/directories/military-positions.php">К версиям</a>
</div></div></header>
<main class="admin-main"><div class="container military-position-layout">
    <?php if ($success !== null): ?><div class="form-message is-success is-visible"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="form-message is-error is-visible"><?= e($error) ?></div><?php endif; ?>

    <?php $versionCardMode = 'detail'; ?>
    <?php require __DIR__ . '/views/version-card.php'; ?>

    <?php if ($sources !== []): ?><section class="military-position-panel glass-tile"><h2>Источники исторической версии</h2><div class="military-position-source-list"><?php foreach ($sources as $source): ?><article><strong><?= e((string) $source['document_type']) ?> от <?= e(military_positions_date((string) $source['document_date'])) ?> № <?= e((string) $source['document_number']) ?></strong><p><?= e((string) $source['title']) ?></p><a href="<?= e((string) $source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Официальный источник →</a></article><?php endforeach; ?></div></section><?php endif; ?>

    <?php if ($canPublish): ?>
    <section class="military-position-lifecycle-grid">
        <form method="post" action="/admin/directories/military-positions/versions/publish.php" class="military-position-panel glass-tile">
            <h2>Опубликовать версию</h2><p>Текущая опубликованная версия станет исторической. Операция атомарна.</p>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= $versionId ?>"><input type="hidden" name="expected_catalog_revision" value="<?= (int) $version['revision'] ?>"><input type="hidden" name="return_to" value="/admin/directories/military-positions/version.php?id=<?= $versionId ?>">
            <label>Основание публикации<textarea name="change_reason" maxlength="1000" required></textarea></label><button class="primary-button" type="submit">Опубликовать</button>
        </form>
        <form method="post" action="/admin/directories/military-positions/versions/cancel.php" class="military-position-panel glass-tile">
            <h2>Отменить черновик</h2><p>Отменённая версия останется доступной только для чтения.</p>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= $versionId ?>"><input type="hidden" name="expected_catalog_revision" value="<?= (int) $version['revision'] ?>"><input type="hidden" name="return_to" value="/admin/directories/military-positions/version.php?id=<?= $versionId ?>">
            <label>Причина отмены<textarea name="change_reason" maxlength="1000" required></textarea></label><button class="secondary-button" type="submit">Отменить черновик</button>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($canManage): ?>
    <details class="military-position-panel glass-tile military-position-create-panel">
        <summary>Добавить воинскую должность</summary>
        <?php
        $entryForm = ['name'=>'','full_name'=>'','short_name'=>'','is_combined'=>0,'source_type'=>'local','source_reference'=>'','note'=>'','sort_order'=>(int) $version['entry_count'] + 1];
        $formAction = '/admin/directories/military-positions/entries/create.php';
        $submitLabel = 'Добавить должность';
        $entryId = null;
        $entryRevision = null;
        require __DIR__ . '/views/entry-form.php';
        ?>
    </details>
    <?php endif; ?>

    <section class="military-position-panel glass-tile">
        <div class="directory-panel-heading"><div><h2>Канонические наименования</h2><p><?= $version['catalog_kind'] === 'legacy' ? 'Историческая версия доступна без изменения.' : 'ВУС, звание, подразделение и военнослужащий не являются свойствами должности.' ?></p></div><span class="directory-result-count">Найдено: <?= (int) $result['total'] ?></span></div>
        <form class="military-position-filters" method="get" action="/admin/directories/military-positions/version.php">
            <input type="hidden" name="id" value="<?= $versionId ?>">
            <label>Поиск<input type="search" name="q" maxlength="150" value="<?= e($query) ?>"></label>
            <label>Состояние<select name="status"><option value="">Все</option><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Действующие</option><option value="archived"<?= $status === 'archived' ? ' selected' : '' ?>>Архивные</option></select></label>
            <label>Составная<select name="is_combined"><option value="">Все</option><option value="1"<?= $combined === '1' ? ' selected' : '' ?>>Да</option><option value="0"<?= $combined === '0' ? ' selected' : '' ?>>Нет</option></select></label>
            <label>Источник<select name="source_type"><option value="">Все</option><option value="official"<?= $sourceType === 'official' ? ' selected' : '' ?>>Официальный</option><option value="local"<?= $sourceType === 'local' ? ' selected' : '' ?>>Локальный</option><option value="imported"<?= $sourceType === 'imported' ? ' selected' : '' ?>>Импортированный</option></select></label>
            <button class="primary-button" type="submit">Применить</button><a class="secondary-button" href="/admin/directories/military-positions/version.php?id=<?= $versionId ?>">Сбросить</a>
        </form>
    </section>

    <section class="military-position-entry-list" aria-label="Воинские должности">
        <?php if ($result['items'] === []): ?><article class="military-position-empty glass-tile"><h2>Должности не найдены</h2><p>Измените фильтры или добавьте запись в черновик.</p></article><?php else: ?>
        <?php foreach ($result['items'] as $entry): ?><?php require __DIR__ . '/views/entry-card.php'; ?><?php endforeach; ?>
        <?php endif; ?>
    </section>
</div></main>
</body>
</html>
