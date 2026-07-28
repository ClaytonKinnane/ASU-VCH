<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Organization/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$user = require_permission('organization.structures.view');
$canCreate = has_permission('organization.structures.create');
$query = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$structures = organizational_structure_repository()->listStructures($query, $status);
$catalog = organizational_element_catalog_repository()->currentVersion();
$rootTypes = organizational_structure_repository()->rootTypesForCatalog((int) $catalog['id']);
$success = flash('success');
$error = flash('error');
$domainError = flash('organization_error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Организационная структура — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Организационная структура</h1><p class="site-description">Фактические структуры, версии и основная штатная подчинённость</p></div>
    <a class="secondary-button" href="/admin/content.php">К контенту</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <?php if ($domainError !== null): ?><div class="form-message is-error is-visible"><?= e($domainError) ?></div><?php endif; ?>
    <section class="organization-toolbar glass-tile">
        <form method="get" class="organization-filter-form">
            <label>Поиск<input type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Название или код"></label>
            <label>Состояние<select name="status"><option value="">Все</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Действующие</option><option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Архивные</option></select></label>
            <button class="primary-button" type="submit">Применить</button>
            <a class="secondary-button" href="/admin/organization/structures.php">Сбросить</a>
        </form>
    </section>

    <?php if ($canCreate): ?>
    <details class="organization-panel glass-tile">
        <summary>Создать организационную структуру</summary>
        <form method="post" action="/admin/organization/structures/create.php" class="organization-form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Внутренний код<input name="code" maxlength="64" required pattern="[a-z0-9][a-z0-9-]{1,63}" placeholder="unit-structure"></label>
            <label>Название структуры<input name="display_name" maxlength="255" required></label>
            <label>Краткое название<input name="short_name" maxlength="128"></label>
            <label>Тип корневого элемента<select name="root_type_id" required><?php foreach ($rootTypes as $type): ?><option value="<?= (int) $type['id'] ?>"><?= e((string) $type['name']) ?></option><?php endforeach; ?></select></label>
            <label class="span-2">Официальное наименование воинской части<input name="root_name" maxlength="255" required></label>
            <label>Краткое наименование корня<input name="root_short_name" maxlength="128"></label>
            <label class="span-2">Основание создания<textarea name="change_reason" maxlength="1000" required></textarea></label>
            <div class="span-2"><button class="primary-button" type="submit">Создать структуру</button></div>
        </form>
    </details>
    <?php endif; ?>

    <section class="organization-list" aria-label="Список организационных структур">
        <?php if ($structures === []): ?>
            <article class="organization-empty glass-tile"><h2>Структуры не найдены</h2><p>Измените параметры поиска или создайте первую структуру при наличии разрешения.</p></article>
        <?php else: ?>
            <?php foreach ($structures as $structure): ?>
            <article class="organization-card glass-tile">
                <div><span class="status-badge <?= $structure['status'] === 'archived' ? 'is-muted' : '' ?>"><?= $structure['status'] === 'archived' ? 'Архивная' : 'Действующая' ?></span>
                    <h2><?= e((string) $structure['display_name']) ?></h2>
                    <p><?= e((string) ($structure['short_name'] ?: $structure['code'])) ?></p>
                </div>
                <dl class="organization-metrics">
                    <div><dt>Действующая версия</dt><dd><?= $structure['active_version_number'] !== null ? '№ ' . (int) $structure['active_version_number'] : 'Нет' ?></dd></div>
                    <div><dt>Дата действия</dt><dd><?= e((string) ($structure['active_effective_from'] ?? '—')) ?></dd></div>
                    <div><dt>Незавершённая версия</dt><dd><?= e(match ((string) ($structure['pending_status'] ?? '')) { 'draft' => 'Черновик', 'approved' => 'Утверждена', default => 'Нет' }) ?></dd></div>
                </dl>
                <a class="primary-button" href="/admin/organization/structure.php?id=<?= (int) $structure['id'] ?>">Открыть</a>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <p class="organization-footnote">Классификатор: <?= e((string) $catalog['name']) ?> · <?= e((string) $catalog['code']) ?></p>
</div></main>
</body>
</html>
