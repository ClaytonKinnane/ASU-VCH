<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_permission('system.*.*');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') > 150) {
    $query = mb_substr($query, 0, 150, 'UTF-8');
}
$requestedVersionCode = trim((string) ($_GET['version'] ?? ''));
if (strlen($requestedVersionCode) > 120) {
    $requestedVersionCode = substr($requestedVersionCode, 0, 120);
}

$repository = military_rank_catalog_repository();
$versionNotice = null;
try {
    $versions = $repository->visibleVersions();
    try {
        $version = $repository->version($requestedVersionCode);
    } catch (OutOfBoundsException) {
        $versionNotice = 'Запрошенная версия справочника не найдена. Показана текущая версия.';
        $version = $repository->currentVersion();
    }
    $sources = $repository->sources($version['id']);
    $compositions = $repository->compositions($version['id']);
} catch (RuntimeException) {
    http_response_code(503);
    ?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Справочник временно недоступен — АСУ-ВЧ</title><link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>"><link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>"></head><body><main class="site-main"><section class="auth-card glass-tile"><h1 class="auth-heading">Справочник временно недоступен</h1><p class="auth-description">Текущая версия справочника не определена либо данные не прошли проверку целостности. Обратитесь к владельцу системы.</p><a class="secondary-button" href="/admin/directories.php">К справочникам</a></section></main></body></html>
<?php
    exit;
}

$compositionCode = trim((string) ($_GET['composition'] ?? ''));
$allowedCompositionCodes = [];
foreach ($compositions as $composition) {
    $allowedCompositionCodes[(string) $composition['code']] = true;
}
if ($compositionCode !== '' && !isset($allowedCompositionCodes[$compositionCode])) {
    $compositionCode = '';
}
$result = $repository->search($query, $compositionCode, $version['id']);

function rank_directory_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
}

function rank_directory_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i');
    } catch (Throwable) {
        return $value;
    }
}

function rank_source_role(string $role): string
{
    return match ($role) {
        'primary-list' => 'Основной перечень',
        'equivalence-and-order' => 'Соответствие и старшинство',
        default => 'Нормативный источник',
    };
}

function rank_version_state(string $status): string
{
    return $status === 'published' ? 'Текущая опубликованная версия' : 'Предыдущая опубликованная версия';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Составы военнослужащих и воинские звания — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/military-ranks-v2.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Составы военнослужащих и воинские звания</h1><p class="site-description">Нормативный системный справочник Российской Федерации</p></div><a class="secondary-button" href="/admin/directories.php">К справочникам</a></div></div></header>
<main class="admin-main"><div class="container">
<?php if ($versionNotice !== null): ?><div class="directory-notice" role="alert"><?= e($versionNotice) ?></div><?php endif; ?>
<section class="directory-hero glass-tile" aria-labelledby="directory-version-title">
    <div class="directory-hero-heading"><div><span class="directory-version-state"><?= e(rank_version_state($version['lifecycle_status'])) ?></span><h2 id="directory-version-title"><?= e($version['name']) ?></h2><p>Данные доступны только для просмотра. Порядок строк соответствует нормативному старшинству воинских званий.</p></div><span class="directory-readonly-badge">Только чтение</span></div>
    <dl class="directory-meta">
        <div><dt>Версия каталога</dt><dd><?= e($version['code']) ?></dd></div>
        <div><dt>Период действия</dt><dd>с <?= e(rank_directory_date($version['valid_from'])) ?><?= $version['valid_to'] !== null ? ' по ' . e(rank_directory_date($version['valid_to'])) : '' ?></dd></div>
        <div><dt>Актуальность проверена</dt><dd><?= e(rank_directory_date($version['verified_at'])) ?></dd></div>
        <div><dt>Опубликована</dt><dd><?= e(rank_directory_datetime($version['published_at'])) ?></dd></div>
    </dl>
</section>
<section class="directory-version-panel glass-tile" aria-labelledby="version-select-title"><div><h2 id="version-select-title">Версия справочника</h2><p>Текущая и предыдущие опубликованные версии сохраняются для исторического просмотра.</p></div><form method="get" action="/admin/directories/military-ranks.php" class="directory-version-form"><label><span>Показать версию</span><select class="form-input" name="version" onchange="this.form.submit()"> <?php foreach ($versions as $available): ?><option value="<?= e($available['code']) ?>"<?= $available['id'] === $version['id'] ? ' selected' : '' ?>><?= e(($available['is_current'] === 1 ? 'Текущая — ' : 'Историческая — ') . $available['code']) ?></option><?php endforeach; ?></select></label><noscript><button class="primary-button" type="submit">Открыть</button></noscript></form></section>
<section class="directory-composition-panel glass-tile" aria-labelledby="composition-title"><div class="directory-panel-heading"><div><h2 id="composition-title">Составы и категории</h2><p>Прикладные категории явно отделены от нормативных составов.</p></div></div><div class="directory-composition-grid">
<?php foreach ($compositions as $composition): ?><article class="directory-composition-card<?= $composition['parent_id'] !== null ? ' is-child' : '' ?>"><strong><?= e($composition['path']) ?></strong><div class="directory-composition-badges"><?php if ($composition['classification_kind'] === 'derived-staffing-scope'): ?><span class="directory-derived-badge">Категория для штатных должностей</span><?php endif; ?><?php if ($composition['is_staffing_selectable'] === 1): ?><span class="directory-staffing-badge">Допустима для штатных должностей</span><?php endif; ?></div><?php if ($composition['derivation_note'] !== null): ?><p><?= e($composition['derivation_note']) ?></p><small>Прикладная подкатегория АСУ-ВЧ. Не является отдельным нормативным составом военнослужащих.</small><?php elseif ($composition['classification_kind'] === null): ?><small>Для этой исторической версии пригодность для штатных должностей не определена.</small><?php endif; ?></article><?php endforeach; ?>
</div></section>
<section class="legal-sources-grid" aria-label="Нормативные источники"><?php foreach ($sources as $source): ?><article class="legal-source-card glass-tile"><span class="legal-source-kicker"><?= e(rank_source_role($source['source_role'])) ?></span><h2><?= e($source['document_type']) ?> от <?= e(rank_directory_date($source['document_date'])) ?> № <?= e($source['document_number']) ?></h2><p><?= e($source['title']) ?></p><dl><div><dt>Положение</dt><dd><?= e($source['provision']) ?></dd></div><div><dt>Проверено</dt><dd><?= e(rank_directory_date($source['verified_at'])) ?></dd></div></dl><a class="legal-source-link" href="<?= e($source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть официальный источник →</a></article><?php endforeach; ?></section>
<section class="directory-panel glass-tile" aria-labelledby="rank-list-title"><div class="directory-panel-heading"><div><h2 id="rank-list-title">Перечень воинских званий</h2><p>Поиск выполняется по войсковым и корабельным наименованиям выбранной версии.</p></div><span class="directory-result-count">Найдено: <?= (int) $result['total'] ?></span></div>
<form class="directory-filters directory-rank-filters" method="get" action="/admin/directories/military-ranks.php"><input type="hidden" name="version" value="<?= e($version['code']) ?>"><label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Например: сержант или адмирал"></label><label><span>Состав военнослужащих</span><select class="form-input" name="composition"><option value="">Все составы</option><?php foreach ($compositions as $composition): ?><?php $label = $composition['parent_name'] !== null ? '— ' . $composition['name'] : $composition['name']; ?><option value="<?= e($composition['code']) ?>"<?= $compositionCode === $composition['code'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><button class="primary-button directory-filter-submit" type="submit">Показать</button><?php if ($query !== '' || $compositionCode !== ''): ?><a class="secondary-button" href="/admin/directories/military-ranks.php?version=<?= rawurlencode($version['code']) ?>">Сбросить</a><?php endif; ?></form>
<?php if ($result['items'] === []): ?><div class="directory-empty"><strong>По заданным условиям воинские звания не найдены.</strong></div><?php else: ?><div class="directory-table-wrap"><table class="directory-table"><thead><tr><th>№</th><th>Состав военнослужащих</th><th>Войсковое звание</th><th>Корабельное звание</th></tr></thead><tbody><?php $previousPath = null; foreach ($result['items'] as $rank): $groupStart = $previousPath !== $rank['composition_path']; $previousPath = $rank['composition_path']; ?><tr<?= $groupStart ? ' class="rank-group-start"' : '' ?>><td data-label="№"><span class="directory-rank-number"><?= (int) $rank['sort_order'] ?></span></td><td data-label="Состав"><span class="directory-composition-path"><?= e($rank['composition_path']) ?></span><?php if ($rank['classification_kind'] === 'derived-staffing-scope'): ?><span class="directory-derived-badge directory-derived-badge-inline">Категория для штатных должностей</span><?php endif; ?></td><td data-label="Войсковое звание"><strong><?= e($rank['troop_name']) ?></strong></td><td data-label="Корабельное звание"><?= $rank['naval_name'] !== null ? '<strong>' . e($rank['naval_name']) . '</strong>' : '<span class="directory-muted-value">—</span>' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section></div></main></body></html>
