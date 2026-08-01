<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_permission('system.*.*');

$repo = military_occupational_specialty_catalog_repository();
$version = $repo->currentVersion();
$versionId = (int) $version['id'];
$sources = $repo->versionLegalSources($versionId);
$snapshots = $repo->officialSourceSnapshots($versionId);
$segments = $repo->codeSegments($versionId);
$domains = $repo->publicContextDomains($versionId);
$scopes = $repo->personnelScopes($versionId);
$organizations = $repo->trainingOrganizations($versionId);

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 150, 'UTF-8');
$recordType = (string) ($_GET['record_type'] ?? 'all');
if (!in_array($recordType, ['all', 'direct-disclosure', 'training-program'], true)) {
    $recordType = 'all';
}
$identifierKinds = ['none', 'base-specialty-number', 'full-code-complete', 'official-program-identifier'];
$identifierKind = (string) ($_GET['identifier_kind'] ?? '');
if ($identifierKind !== '' && !in_array($identifierKind, $identifierKinds, true)) {
    $identifierKind = '';
}
$scope = (string) ($_GET['personnel_scope'] ?? '');
if ($scope !== '' && !isset(array_column($scopes, null, 'code')[$scope])) {
    $scope = '';
}
$organization = (string) ($_GET['organization'] ?? '');
if ($organization !== '' && !isset(array_column($organizations, null, 'code')[$organization])) {
    $organization = '';
}
$evidenceLevels = [
    'official-form-example',
    'official-program-code',
    'official-program-qualification',
    'official-program-code-and-qualification',
];
$evidence = (string) ($_GET['evidence_level'] ?? '');
if ($evidence !== '' && !in_array($evidence, $evidenceLevels, true)) {
    $evidence = '';
}
$status = (string) ($_GET['currency_status'] ?? '');
if ($status !== '' && !in_array($status, ['current', 'historical', 'unavailable'], true)) {
    $status = '';
}

$direct = ['items' => [], 'total' => 0];
if ($recordType !== 'training-program') {
    $direct = $repo->searchPublicDisclosures($versionId, $q, '', $identifierKind, $scope, '', $evidence, $status);
}
$programs = ['items' => [], 'total' => 0];
if ($recordType !== 'direct-disclosure') {
    $programs = $repo->searchTrainingPrograms($versionId, $q, $identifierKind, $scope, $organization, '', $evidence, $status);
}
$total = (int) $direct['total'] + (int) $programs['total'];
$hasFilters = $q !== ''
    || $recordType !== 'all'
    || $identifierKind !== ''
    || $scope !== ''
    || $organization !== ''
    || $evidence !== ''
    || $status !== '';

function mos_date(?string $value): string
{
    if (!$value) {
        return 'Не указана';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date ? $date->format('d.m.Y') : $value;
}

function mos_identifier_name(string $kind): string
{
    return match ($kind) {
        'none' => 'Код не раскрыт',
        'base-specialty-number' => 'Трёхзначный номер ВУС',
        'full-code-complete' => 'Полное кодовое обозначение',
        'official-program-identifier' => 'Обозначение официальной программы',
        default => 'Неизвестный вид обозначения',
    };
}

function mos_source_role_name(string $role): string
{
    return match ($role) {
        'legal-basis' => 'Правовая основа',
        'code-structure-base' => 'Структура кода',
        'code-structure-amendment' => 'Изменение структуры кода',
        'current-edition-amendment' => 'Изменение действующей редакции',
        'public-context-list' => 'Публичные области',
        default => 'Нормативный источник',
    };
}

function mos_evidence_name(string $level): string
{
    return match ($level) {
        'official-form-example' => 'Официальный пример заполнения документа',
        'official-program-code' => 'Код опубликован официальной программой подготовки',
        'official-program-qualification' => 'Квалификация опубликована официальной программой подготовки',
        'official-program-code-and-qualification' => 'Код и квалификация опубликованы официальной программой подготовки',
        default => 'Официальное публичное подтверждение',
    };
}

function mos_status_name(string $status): string
{
    return match ($status) {
        'current' => 'Актуальные',
        'historical' => 'Исторические',
        'unavailable' => 'Источник недоступен',
        default => 'Все',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Военно-учётные специальности — АСУ-ВЧ</title>
<link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
<link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
<link rel="stylesheet" href="<?= e(theme_asset('css/military-occupational-specialties.css')) ?>">
</head>
<body class="mos-page">
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Военно-учётные специальности</h1><p class="site-description">Публичные сведения и официальные программы подготовки</p></div><a class="secondary-button" href="/admin/directories.php">К справочникам</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="directory-hero glass-tile"><div class="directory-hero-heading"><div><h2><?= e((string) $version['name']) ?></h2><p><?= e((string) $version['coverage_note']) ?></p><p><strong>Важно:</strong> справочник содержит только сведения, прямо раскрытые в открытых официальных источниках.</p><p>Он не является полным перечнем ВУС, не определяет мобилизационное предназначение, не устанавливает исчерпывающее соответствие конкретной штатной должности и не предназначен для персонального воинского учёта.</p></div><span class="directory-readonly-badge">Только публичные сведения</span></div><dl class="directory-meta"><div><dt>Версия</dt><dd><?= e((string) $version['code']) ?></dd></div><div><dt>Действует с</dt><dd><?= e(mos_date((string) $version['valid_from'])) ?></dd></div><div><dt>Проверено</dt><dd><?= e(mos_date((string) $version['verified_at'])) ?></dd></div></dl></section>

<section class="legal-sources-grid mos-section" aria-label="Нормативные источники"><?php foreach ($sources as $item): ?><article class="legal-source-card directory-linked-card glass-tile"><span class="legal-source-kicker"><?= e(mos_source_role_name((string) $item['source_role'])) ?></span><h2><?= e((string) $item['document_type']) ?> № <?= e((string) $item['document_number']) ?></h2><p><?= e((string) $item['title']) ?></p><p><?= e((string) $item['provision_detail']) ?></p><a class="legal-source-link" href="<?= e((string) $item['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть источник →</a></article><?php endforeach; ?></section>

<section class="directory-panel glass-tile"><div class="directory-panel-heading"><div><h2>Официальные институциональные источники</h2><p>Официальные страницы образовательных организаций, на которых непосредственно опубликованы коды или квалификации.</p></div><span class="directory-result-count"><?= count($snapshots) ?></span></div><div class="legal-sources-grid directory-panel-grid"><?php foreach ($snapshots as $item): ?><article class="legal-source-card directory-linked-card glass-tile"><h2><?= e((string) $item['publisher_name']) ?></h2><p><?= e((string) $item['evidence_summary']) ?></p><a class="legal-source-link" href="<?= e((string) $item['source_url']) ?>" target="_blank" rel="noopener noreferrer">Официальная страница →</a></article><?php endforeach; ?></div></section>

<section class="directory-panel glass-tile"><div class="directory-panel-heading"><div><h2>Структура полного кода</h2><p>Механическое выделение частей кода не является смысловой расшифровкой их значений.</p></div></div><div class="module-grid"><?php foreach ($segments as $item): ?><article class="directory-info-card module-tile glass-tile"><span class="tile-kicker">Позиции <?= (int) $item['position_from'] ?>–<?= (int) $item['position_to'] ?></span><h2><?= e((string) $item['name']) ?></h2><p><?= e((string) $item['description']) ?></p></article><?php endforeach; ?></div></section>

<section class="directory-panel glass-tile"><div class="directory-panel-heading"><div><h2>Публичные области профессий и образования</h2><p>Это не группы ВУС и не полный классификатор.</p></div><span class="directory-result-count"><?= count($domains) ?></span></div><div class="module-grid"><?php foreach ($domains as $item): ?><article class="directory-info-card module-tile glass-tile"><h2><?= e((string) $item['name']) ?></h2><p><?= e((string) $item['description']) ?></p><small><?= e((string) $item['coverage_note']) ?></small></article><?php endforeach; ?></div></section>

<section class="directory-panel glass-tile"><div class="directory-panel-heading"><div><h2>Публично раскрытые записи</h2><p>Одна строка представляет одно прямое раскрытие одного официального источника.</p></div><span class="directory-result-count">Найдено: <?= $total ?></span></div>
<form class="directory-filters mos-directory-filters" method="get" action="/admin/directories/military-occupational-specialties.php">
<label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($q) ?>" placeholder="Код или квалификация"></label>
<label><span>Тип записи</span><select class="form-input" name="record_type"><option value="all"<?= $recordType === 'all' ? ' selected' : '' ?>>Все</option><option value="direct-disclosure"<?= $recordType === 'direct-disclosure' ? ' selected' : '' ?>>Нормативные примеры</option><option value="training-program"<?= $recordType === 'training-program' ? ' selected' : '' ?>>Программы подготовки</option></select></label>
<label><span>Вид обозначения</span><select class="form-input" name="identifier_kind"><option value="">Все</option><?php foreach ($identifierKinds as $item): ?><option value="<?= e($item) ?>"<?= $identifierKind === $item ? ' selected' : '' ?>><?= e(mos_identifier_name($item)) ?></option><?php endforeach; ?></select></label>
<label><span>Категория подготовки</span><select class="form-input" name="personnel_scope"><option value="">Все</option><?php foreach ($scopes as $item): ?><option value="<?= e((string) $item['code']) ?>"<?= $scope === $item['code'] ? ' selected' : '' ?>><?= e((string) $item['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Организация</span><select class="form-input" name="organization"><option value="">Все</option><?php foreach ($organizations as $item): ?><option value="<?= e((string) $item['code']) ?>"<?= $organization === $item['code'] ? ' selected' : '' ?>><?= e((string) $item['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Основание публикации</span><select class="form-input" name="evidence_level"><option value="">Все</option><?php foreach ($evidenceLevels as $item): ?><option value="<?= e($item) ?>"<?= $evidence === $item ? ' selected' : '' ?>><?= e(mos_evidence_name($item)) ?></option><?php endforeach; ?></select></label>
<label><span>Статус источника</span><select class="form-input" name="currency_status"><option value="">Все</option><?php foreach (['current', 'historical', 'unavailable'] as $item): ?><option value="<?= e($item) ?>"<?= $status === $item ? ' selected' : '' ?>><?= e(mos_status_name($item)) ?></option><?php endforeach; ?></select></label>
<button class="primary-button directory-filter-submit" type="submit">Показать</button><?php if ($hasFilters): ?><a class="secondary-button mos-filter-reset" href="/admin/directories/military-occupational-specialties.php">Сбросить</a><?php endif; ?></form>

<?php if ($total === 0): ?><div class="directory-empty"><div><strong>По заданным условиям публичные записи не найдены.</strong></div></div><?php else: ?>
<div class="directory-table-wrap"><table class="directory-table mos-records-table"><thead><tr><th>Источник</th><th>Обозначение</th><th>Квалификация</th><th>Категория и основание</th></tr></thead><tbody>
<?php foreach ($direct['items'] as $item): ?><tr><td data-label="Источник"><a href="<?= e((string) $item['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $item['source_title']) ?></a></td><td data-label="Обозначение"><strong><?= e((string) ($item['raw_identifier'] ?? 'Не раскрыто')) ?></strong><small><?= e(mos_identifier_name((string) $item['identifier_kind'])) ?></small></td><td data-label="Квалификация"><?= e((string) ($item['qualification_name'] ?? 'Не раскрыта')) ?></td><td data-label="Категория и основание"><strong><?= e(mos_evidence_name((string) $item['evidence_level'])) ?></strong><p><?= e((string) $item['evidence_summary']) ?></p></td></tr><?php endforeach; ?>
<?php foreach ($programs['items'] as $item): ?><tr><td data-label="Источник"><a href="<?= e((string) $item['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $item['organization_name']) ?></a></td><td data-label="Обозначение"><strong><?= e((string) ($item['raw_identifier'] ?? 'Цифровой код публичным источником не раскрыт')) ?></strong><small><?= e(mos_identifier_name((string) $item['identifier_kind'])) ?></small></td><td data-label="Квалификация"><?= e((string) ($item['qualification_name'] ?? 'Наименование квалификации публичным источником не раскрыто')) ?></td><td data-label="Категория и основание"><strong><?= e((string) $item['personnel_scope_name']) ?></strong><p><?= e(mos_evidence_name((string) $item['evidence_level'])) ?></p><p><?= e((string) $item['source_phrase']) ?></p></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<div class="directory-empty directory-boundary-note"><div><strong>Связи с типами воинских должностей не публикуются.</strong><p>В текущей версии нет функции сопоставления ВУС с должностями, воинскими званиями, ВВСТ или персональными данными.</p></div></div>
</section>
</div></main></body></html>
