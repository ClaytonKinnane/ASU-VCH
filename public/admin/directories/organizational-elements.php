<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Directory/OrganizationalElementCatalogRepository.php';

$user = require_permission('system.*.*');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') > 150) {
    $query = mb_substr($query, 0, 150, 'UTF-8');
}

$repository = new OrganizationalElementCatalogRepository(db());
$version = $repository->currentVersion();
$versionId = (int) $version['id'];
$sources = $repository->versionSources($versionId);
$classes = $repository->classes($versionId);

$classCode = trim((string) ($_GET['class'] ?? ''));
$allowedClassCodes = [];
foreach ($classes as $class) {
    $allowedClassCodes[(string) $class['code']] = true;
}
if ($classCode !== '' && !isset($allowedClassCodes[$classCode])) {
    $classCode = '';
}

$scope = trim((string) ($_GET['scope'] ?? ''));
$allowedScopes = ['non_subdivision_only', 'subdivision_only', 'mixed'];
if ($scope !== '' && !in_array($scope, $allowedScopes, true)) {
    $scope = '';
}

$result = $repository->searchTypes($versionId, $query, $classCode, $scope);
$typeIds = array_map(
    static fn(array $item): int => (int) $item['id'],
    $result['items']
);
$classesByType = $repository->classesForTypes($versionId, $typeIds);
$sourcesByType = $repository->sourcesForTypes($versionId, $typeIds);
$aliasesByType = $repository->aliasesForTypes($versionId, $typeIds);

function organizational_element_directory_date(string $value): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
}

function organizational_element_version_source_role(string $role): string
{
    return match ($role) {
        'general-composition' => 'Общий состав Вооружённых Сил',
        'classification' => 'Нормативная классификация',
        'internal-service' => 'Общевоинская организация',
        'naval-organization' => 'Корабельная организация',
        default => 'Официальный источник',
    };
}

function organizational_element_type_source_role(string $role): string
{
    return match ($role) {
        'definition' => 'Определение',
        'classification' => 'Классификация',
        'official-usage' => 'Официальное употребление',
        'authority-rule' => 'Полномочия и порядок',
        'historical-context' => 'Исторический контекст',
        default => 'Основание',
    };
}

/** @param list<array{code:string}> $typeClasses */
function organizational_element_scope(array $typeClasses): string
{
    $hasSubdivision = false;
    $hasOther = false;
    foreach ($typeClasses as $class) {
        if (($class['code'] ?? '') === 'subdivision') {
            $hasSubdivision = true;
        } else {
            $hasOther = true;
        }
    }

    if ($hasSubdivision && $hasOther) {
        return 'mixed';
    }
    if ($hasSubdivision) {
        return 'subdivision_only';
    }
    return 'non_subdivision_only';
}

function organizational_element_scope_label(string $scope): string
{
    return match ($scope) {
        'non_subdivision_only' => 'Не является подразделением',
        'subdivision_only' => 'Только подразделение',
        'mixed' => 'Зависит от утверждённой структуры',
        default => 'Не определён',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Типы организационных элементов — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Типы организационных элементов</h1><p class="site-description">Нормативно-методический классификатор общих типов</p></div><a class="secondary-button" href="/admin/directories.php">К справочникам</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="directory-hero glass-tile" aria-labelledby="organizational-elements-version-title">
    <div class="directory-hero-heading"><div><h2 id="organizational-elements-version-title"><?= e($version['name']) ?></h2><p>Справочник содержит общие типы организационных элементов. Он не является утверждённым штатом или организационной структурой конкретной воинской части и не определяет фактическую подчинённость.</p></div><span class="directory-readonly-badge">Только чтение</span></div>
    <dl class="directory-meta">
        <div><dt>Версия каталога</dt><dd><?= e($version['code']) ?></dd></div>
        <div><dt>Действует в АСУ-ВЧ с</dt><dd><?= e(organizational_element_directory_date($version['valid_from'])) ?></dd></div>
        <div><dt>Актуальность проверена</dt><dd><?= e(organizational_element_directory_date($version['verified_at'])) ?></dd></div>
    </dl>
</section>
<section class="legal-sources-grid organizational-source-grid" aria-label="Нормативные и официальные источники">
<?php foreach ($sources as $source): ?>
<article class="legal-source-card glass-tile">
    <span class="legal-source-kicker"><?= e(organizational_element_version_source_role($source['source_role'])) ?></span>
    <h2><?= e($source['document_type']) ?> от <?= e(organizational_element_directory_date($source['document_date'])) ?> № <?= e($source['document_number']) ?></h2>
    <p><?= e($source['title']) ?></p>
    <dl>
        <div><dt>Положение</dt><dd><?= e($source['provision']) ?></dd></div>
        <div><dt>Проверено</dt><dd><?= e(organizational_element_directory_date($source['verified_at'])) ?></dd></div>
    </dl>
    <a class="legal-source-link" href="<?= e($source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть официальный источник →</a>
</article>
<?php endforeach; ?>
</section>
<section class="directory-panel glass-tile" aria-labelledby="organizational-elements-list-title">
    <div class="directory-panel-heading"><div><h2 id="organizational-elements-list-title">Классификатор типов</h2><p>Поиск выполняется по полному, сокращённому и подтверждённым вариантам наименования.</p></div><span class="directory-result-count">Найдено: <?= (int) $result['total'] ?></span></div>
    <form class="directory-filters organizational-element-filters" method="get" action="/admin/directories/organizational-elements.php">
        <label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Например: батальон или БЧ"></label>
        <label><span>Организационный класс</span><select class="form-input" name="class"><option value="">Все классы</option><?php foreach ($classes as $class): ?><option value="<?= e($class['code']) ?>"<?= $classCode === $class['code'] ? ' selected' : '' ?>><?= e($class['name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Организационный статус</span><select class="form-input" name="scope"><option value="">Все статусы</option><option value="non_subdivision_only"<?= $scope === 'non_subdivision_only' ? ' selected' : '' ?>>Не является подразделением</option><option value="subdivision_only"<?= $scope === 'subdivision_only' ? ' selected' : '' ?>>Только подразделение</option><option value="mixed"<?= $scope === 'mixed' ? ' selected' : '' ?>>Зависит от утверждённой структуры</option></select></label>
        <button class="primary-button directory-filter-submit" type="submit">Показать</button>
        <?php if ($query !== '' || $classCode !== '' || $scope !== ''): ?><a class="secondary-button" href="/admin/directories/organizational-elements.php">Сбросить</a><?php endif; ?>
    </form>
    <?php if ($result['items'] === []): ?>
    <div class="directory-empty"><div><strong>По заданным условиям типы организационных элементов не найдены.</strong><p>Измените поисковый запрос или сбросьте фильтры.</p></div></div>
    <?php else: ?>
    <div class="directory-table-wrap organizational-element-table-wrap"><table class="directory-table organizational-element-table"><thead><tr><th>Тип</th><th>Возможные классы</th><th>Организационный статус</th><th>Основание</th><th>Примечание</th></tr></thead><tbody>
    <?php foreach ($result['items'] as $item): ?>
        <?php
            $typeId = (int) $item['id'];
            $typeClasses = $classesByType[$typeId] ?? [];
            $typeSources = $sourcesByType[$typeId] ?? [];
            $typeAliases = $aliasesByType[$typeId] ?? [];
            $typeScope = organizational_element_scope($typeClasses);
        ?>
        <tr>
            <td data-label="Тип"><strong class="organizational-element-name"><?= e($item['name']) ?></strong><?php if ($item['short_name'] !== null): ?><span class="organizational-element-short-name"><?= e($item['short_name']) ?></span><?php endif; ?><p class="organizational-element-description"><?= e($item['description']) ?></p><?php if ($typeAliases !== []): ?><p class="organizational-element-aliases">Варианты: <?= e(implode(', ', array_column($typeAliases, 'alias'))) ?></p><?php endif; ?></td>
            <td data-label="Возможные классы"><div class="organizational-class-list"><?php foreach ($typeClasses as $class): ?><span class="organizational-class-badge<?= (int) $class['is_primary'] === 1 ? ' is-primary' : '' ?>"><?= e($class['name']) ?><?= (int) $class['is_primary'] === 1 ? ' · основной' : '' ?></span><?php if ($class['context_note'] !== null): ?><small><?= e($class['context_note']) ?></small><?php endif; ?><?php endforeach; ?></div></td>
            <td data-label="Организационный статус"><span class="organizational-scope-badge scope-<?= e($typeScope) ?>"><?= e(organizational_element_scope_label($typeScope)) ?></span></td>
            <td data-label="Основание"><div class="organizational-source-list"><?php foreach ($typeSources as $source): ?><details><summary><?= e(organizational_element_type_source_role($source['source_role'])) ?> — № <?= e($source['document_number']) ?></summary><p><?= e($source['provision_detail']) ?></p><a href="<?= e($source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть источник →</a></details><?php endforeach; ?></div></td>
            <td data-label="Примечание"><?= e($item['applicability_note']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
</div></main>
</body>
</html>
