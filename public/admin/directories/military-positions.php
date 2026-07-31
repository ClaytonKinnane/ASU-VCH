<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('system.*.*');

$query = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') > 150) {
    $query = mb_substr($query, 0, 150, 'UTF-8');
}
$repository = military_position_catalog_repository();
$version = $repository->currentVersion();
$versionId = (int)$version['id'];
$sources = $repository->versionSources($versionId);
$families = $repository->families($versionId);
$compositionScopes = $repository->compositionScopes($versionId);
$organizationalTypes = $repository->organizationalElementTypes($versionId);

$family = trim((string)($_GET['family'] ?? ''));
$allowedFamilies = array_column($families, null, 'code');
if ($family !== '' && !isset($allowedFamilies[$family])) $family='';
$scope = trim((string)($_GET['composition_scope'] ?? ''));
$allowedScopes = array_column($compositionScopes, null, 'code');
if ($scope !== '' && !isset($allowedScopes[$scope])) $scope='';
$org = trim((string)($_GET['organizational_element'] ?? ''));
$allowedOrg = array_column($organizationalTypes, null, 'code');
if ($org !== '' && !isset($allowedOrg[$org])) $org='';
$tariffRaw = trim((string)($_GET['tariff_grade'] ?? ''));
$tariff = ctype_digit($tariffRaw) && (int)$tariffRaw >= 1 && (int)$tariffRaw <= 50 ? (int)$tariffRaw : null;

$result = $repository->searchTypes($versionId,$query,$family,$scope,$tariff,$org);
$typeIds = array_map(static fn(array $row): int => (int)$row['id'],$result['items']);
$variantsByType = $repository->variantsForTypes($versionId,$typeIds);
$familiesByType = $repository->familiesForTypes($versionId,$typeIds);
$scopesByType = $repository->compositionScopesForTypes($versionId,$typeIds);
$orgByType = $repository->organizationalContextsForTypes($versionId,$typeIds);

function mp_date(string $value): string { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value); return $d?$d->format('d.m.Y'):$value; }
function mp_source_role(string $role): string { return match($role){'legal-definition'=>'Правовое определение','service-procedure'=>'Порядок прохождения службы','base-act'=>'Базовый перечень','current-edition-amendment'=>'Изменение действующей редакции',default=>'Официальный источник'}; }
function mp_kind(string $kind): string { return match($kind){'official-exact'=>'Точная формулировка','official-template'=>'Нормативный шаблон','official-variant'=>'Выделенный вариант',default=>'Нормативный вариант'}; }
function mp_context(string $context): string { return match($context){'troop'=>'Войсковой контекст','naval'=>'Корабельный контекст',default=>'Общий контекст'}; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Типовые воинские должности — АСУ-ВЧ</title>
<link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
<link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Типовые воинские должности</h1><p class="site-description">Публичное нормативное ядро классификатора</p></div><a class="secondary-button" href="/admin/directories.php">К справочникам</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="directory-hero glass-tile"><div class="directory-hero-heading"><div><h2><?= e((string)$version['name']) ?></h2><p><?= e((string)$version['coverage_note']) ?></p><p><strong>Важно:</strong> справочник не является полным перечнем штатных должностей, не описывает штат конкретной воинской части и не определяет штатное воинское звание.</p></div><span class="directory-readonly-badge">Только чтение</span></div><dl class="directory-meta"><div><dt>Версия</dt><dd><?= e((string)$version['code']) ?></dd></div><div><dt>Действует в АСУ-ВЧ с</dt><dd><?= e(mp_date((string)$version['valid_from'])) ?></dd></div><div><dt>Актуальность проверена</dt><dd><?= e(mp_date((string)$version['verified_at'])) ?></dd></div></dl></section>
<section class="legal-sources-grid" aria-label="Источники версии"><?php foreach($sources as $source): ?><article class="legal-source-card glass-tile"><span class="legal-source-kicker"><?= e(mp_source_role((string)$source['source_role'])) ?></span><h2><?= e((string)$source['document_type']) ?> от <?= e(mp_date((string)$source['document_date'])) ?> № <?= e((string)$source['document_number']) ?></h2><p><?= e((string)$source['title']) ?></p><dl><div><dt>Положение</dt><dd><?= e((string)$source['provision']) ?></dd></div><div><dt>Проверено</dt><dd><?= e(mp_date((string)$source['verified_at'])) ?></dd></div></dl><a class="legal-source-link" href="<?= e((string)$source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть официальный источник →</a></article><?php endforeach; ?></section>
<section class="directory-panel glass-tile"><div class="directory-panel-heading"><div><h2>Классификатор типов</h2><p>Поиск выполняется по каноническим типам и подтверждённым нормативным вариантам.</p></div><span class="directory-result-count">Найдено: <?= (int)$result['total'] ?></span></div>
<form class="directory-filters organizational-element-filters" method="get" action="/admin/directories/military-positions.php">
<label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Например: командир батальона"></label>
<label><span>Функциональная группа</span><select class="form-input" name="family"><option value="">Все группы</option><?php foreach($families as $item): ?><option value="<?= e((string)$item['code']) ?>"<?= $family===$item['code']?' selected':'' ?>><?= e((string)$item['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Нормативная область</span><select class="form-input" name="composition_scope"><option value="">Все области</option><?php foreach($compositionScopes as $item): ?><option value="<?= e((string)$item['code']) ?>"<?= $scope===$item['code']?' selected':'' ?>><?= e((string)$item['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Тарифный разряд</span><select class="form-input" name="tariff_grade"><option value="">Все разряды</option><?php foreach(range(1,50) as $grade): ?><option value="<?= $grade ?>"<?= $tariff===$grade?' selected':'' ?>><?= $grade ?></option><?php endforeach; ?></select></label>
<label><span>Организационный контекст</span><select class="form-input" name="organizational_element"><option value="">Все типы</option><?php foreach($organizationalTypes as $item): ?><option value="<?= e((string)$item['code']) ?>"<?= $org===$item['code']?' selected':'' ?>><?= e((string)$item['name']) ?></option><?php endforeach; ?></select></label>
<button class="primary-button directory-filter-submit" type="submit">Показать</button><?php if($query!==''||$family!==''||$scope!==''||$tariff!==null||$org!==''): ?><a class="secondary-button" href="/admin/directories/military-positions.php">Сбросить</a><?php endif; ?></form>
<?php if($result['items']===[]): ?><div class="directory-empty"><div><strong>По заданным условиям типы воинских должностей не найдены.</strong><p>Измените запрос или сбросьте фильтры.</p></div></div><?php else: ?>
<div class="directory-table-wrap organizational-element-table-wrap"><table class="directory-table organizational-element-table"><thead><tr><th>Канонический тип</th><th>Нормативные варианты и разряды</th><th>Функциональная группа</th><th>Нормативная область</th><th>Организационный контекст</th></tr></thead><tbody>
<?php foreach($result['items'] as $item): $id=(int)$item['id']; ?>
<tr><td data-label="Канонический тип"><strong class="organizational-element-name"><?= e((string)$item['name']) ?></strong><p class="organizational-element-description"><?= e((string)$item['description']) ?></p><p class="organizational-element-aliases"><?= e((string)$item['applicability_note']) ?></p></td>
<td data-label="Нормативные варианты"><div class="organizational-source-list"><?php foreach($variantsByType[$id]??[] as $variant): ?><details><summary>Разряд <?= (int)$variant['tariff_grade'] ?> · <?= e(mp_kind((string)$variant['designation_kind'])) ?></summary><p><strong><?= e((string)$variant['designation']) ?></strong></p><p><?= e(mp_context((string)$variant['service_context'])) ?> · правило <?= e((string)$variant['normalization_rule']) ?></p><p><?= e((string)$variant['normalization_note']) ?></p></details><?php endforeach; ?></div></td>
<td data-label="Функциональная группа"><div class="organizational-class-list"><?php foreach($familiesByType[$id]??[] as $f): ?><span class="organizational-class-badge<?= (int)$f['is_primary']===1?' is-primary':'' ?>"><?= e((string)$f['name']) ?><?= (int)$f['is_primary']===1?' · основная':'' ?></span><?php endforeach; ?></div></td>
<td data-label="Нормативная область"><div class="organizational-class-list"><?php foreach($scopesByType[$id]??[] as $s): ?><span class="organizational-scope-badge scope-mixed"><?= e((string)$s['name']) ?></span><small><?= e((string)$s['member_names']) ?></small><?php endforeach; ?><small>Штатное звание определяется конкретным штатом и этим справочником не устанавливается.</small></div></td>
<td data-label="Организационный контекст"><div class="organizational-class-list"><?php if(($orgByType[$id]??[])===[]): ?><span class="directory-muted-value">Не установлен публичным ядром</span><?php else: ?><?php foreach($orgByType[$id] as $o): ?><span class="organizational-class-badge"><?= e((string)$o['name']) ?></span><small><?= e((string)$o['normalization_note']) ?></small><?php endforeach; ?><?php endif; ?></div></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</div></main></body></html>
